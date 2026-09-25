<?php

namespace Warext\Clans\Service\Join;

use XF\Service\AbstractService;

class Manager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function joinOpen(\XF\Entity\User $user): \Warext\Clans\Entity\ClanMember
    {
        if ($this->clan->join_mode !== 'open')
        {
            throw new \XF\PrintableException('This clan does not allow instant joining.');
        }
        if (!$this->clan->canJoin($error))
        {
            throw new \XF\PrintableException($error ?: 'You cannot join this clan.');
        }

        $member = $this->service('Warext\\Clans:Clan\\MemberManager', $this->clan)->addMember($user, $user->user_id);
        $this->service('Warext\\Clans:Notification')->alertClanManagers($this->clan, 'member_joined', $user, ['user_id' => $user->user_id]);
        return $member;
    }

    public function submitApplication(\XF\Entity\User $user, array $answers): \Warext\Clans\Entity\ClanApplication
    {
        if ($this->clan->join_mode !== 'application')
        {
            throw new \XF\PrintableException('This clan is not accepting applications.');
        }
        if (!$this->clan->canJoin($error))
        {
            throw new \XF\PrintableException($error ?: 'You cannot apply to this clan.');
        }

        $pending = $this->finder('Warext\\Clans:ClanApplication')
            ->where('application_type', 'join')
            ->where('clan_id', $this->clan->clan_id)
            ->where('user_id', $user->user_id)
            ->where('status', 'pending')
            ->fetchOne();
        if ($pending)
        {
            throw new \XF\PrintableException('You already have a pending application for this clan.');
        }

        $cooldownHours = (int)($this->app->options()->wxClansJoinCooldownHours ?? 0);
        if ($cooldownHours > 0)
        {
            $latest = $this->finder('Warext\\Clans:ClanApplication')
                ->where('application_type', 'join')
                ->where('clan_id', $this->clan->clan_id)
                ->where('user_id', $user->user_id)
                ->where('status', ['rejected','cancelled'])
                ->order('decision_date', 'DESC')
                ->order('create_date', 'DESC')
                ->fetchOne();
            if ($latest)
            {
                $lastDate = $latest->decision_date ?: $latest->create_date;
                $nextAllowed = $lastDate + ($cooldownHours * 3600);
                if ($lastDate > 0 && $nextAllowed > \XF::$time)
                {
                    $remaining = (int)ceil(($nextAllowed - \XF::$time) / 3600);
                    throw new \XF\PrintableException('You can apply to this clan again in approximately ' . $remaining . ' hour(s).');
                }
            }
        }

        $this->service('Warext\\Clans:Clan\\MemberManager', $this->clan)->assertMembershipLimit($user);

        $fields = $this->finder('Warext\\Clans:ClanApplicationField')
            ->where('clan_id', $this->clan->clan_id)
            ->where('active', 1)
            ->order('display_order')
            ->fetch();

        $normalized = [];
        foreach ($fields as $field)
        {
            $raw = $answers[$field->field_id] ?? '';
            $value = is_array($raw) ? array_values(array_filter(array_map('strval', $raw), 'strlen')) : trim((string)$raw);
            if ($field->required && ($value === '' || $value === []))
            {
                throw new \XF\PrintableException($field->title . ' is required.');
            }
            if (in_array($field->field_type, ['select', 'checkbox'], true) && $field->field_options)
            {
                $allowed = array_map('strval', array_values($field->field_options));
                $selected = is_array($value) ? $value : [$value];
                foreach ($selected as $choice)
                {
                    if ($choice !== '' && !in_array($choice, $allowed, true))
                    {
                        throw new \XF\PrintableException('Invalid answer for ' . $field->title . '.');
                    }
                }
            }
            if ($field->field_type === 'number' && $value !== '' && !is_numeric($value))
            {
                throw new \XF\PrintableException($field->title . ' must be a number.');
            }
            if ($field->field_type === 'date' && $value !== '')
            {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string)$value);
                if (!$date || $date->format('Y-m-d') !== (string)$value)
                {
                    throw new \XF\PrintableException($field->title . ' must be a valid date.');
                }
            }
            if (is_string($value))
            {
                $maxLength = $field->field_type === 'textarea' ? 5000 : 500;
                if (mb_strlen($value) > $maxLength)
                {
                    throw new \XF\PrintableException($field->title . ' is too long.');
                }
            }
            $normalized[$field->field_id] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        }

        $application = $this->em()->create('Warext\\Clans:ClanApplication');
        $application->bulkSet([
            'application_type' => 'join',
            'clan_id' => $this->clan->clan_id,
            'user_id' => $user->user_id,
            'status' => 'pending',
            'create_date' => \XF::$time
        ]);
        $application->save();

        foreach ($normalized as $fieldId => $answer)
        {
            $entity = $this->em()->create('Warext\\Clans:ClanApplicationAnswer');
            $entity->bulkSet(['application_id' => $application->application_id, 'field_id' => $fieldId, 'answer' => $answer]);
            $entity->save();
        }

        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $user->user_id, 'join_application_submitted', ['application_id' => $application->application_id], 'clan_application', $application->application_id);
        $this->service('Warext\\Clans:Notification')->alertClanManagers($this->clan, 'join_application', $user, ['application_id' => $application->application_id]);
        return $application;
    }

    public function decideApplication(\Warext\Clans\Entity\ClanApplication $application, bool $approve, \XF\Entity\User $actor, string $reason = ''): void
    {
        if ($application->application_type !== 'join' || $application->clan_id !== $this->clan->clan_id || $application->status !== 'pending')
        {
            throw new \XF\PrintableException('This join application is no longer pending.');
        }

        $applicant = $application->User ?: $this->em()->find('XF:User', $application->user_id);
        if (!$applicant)
        {
            throw new \XF\PrintableException('Applicant account no longer exists.');
        }

        $db = $this->db();
        $db->beginTransaction();
        try
        {
            if ($approve)
            {
                $this->service('Warext\\Clans:Clan\\MemberManager', $this->clan)->addMember($applicant, $actor->user_id);
            }

            $application->status = $approve ? 'approved' : 'rejected';
            $application->decision_date = \XF::$time;
            $application->decision_user_id = $actor->user_id;
            $application->decision_reason = trim($reason);
            $application->save();

            $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actor->user_id, $approve ? 'join_application_approved' : 'join_application_rejected', ['application_id' => $application->application_id, 'user_id' => $application->user_id, 'reason' => trim($reason)], 'clan_application', $application->application_id);
            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        $this->service('Warext\\Clans:Notification')->alertUser($applicant, $this->clan, $approve ? 'application_approved' : 'application_rejected', $actor, ['reason' => trim($reason)]);
    }
}
