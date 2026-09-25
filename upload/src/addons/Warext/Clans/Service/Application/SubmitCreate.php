<?php

namespace Warext\Clans\Service\Application;

use XF\Service\AbstractService;

class SubmitCreate extends AbstractService
{
    protected \XF\Entity\User $user;
    protected array $input = [];
    protected ?\Warext\Clans\Entity\ClanApplication $existing = null;

    public function __construct(\XF\App $app, \XF\Entity\User $user)
    {
        parent::__construct($app);
        $this->user = $user;
    }

    public function setInput(array $input): void
    {
        $this->input = $input;
    }

    public function setExisting(?\Warext\Clans\Entity\ClanApplication $application): void
    {
        if ($application && ($application->application_type !== 'create' || $application->user_id !== $this->user->user_id || $application->status !== 'changes_requested'))
        {
            throw new \LogicException('Invalid clan application.');
        }
        $this->existing = $application;
    }

    public function validate(?array &$errors = null): bool
    {
        $errors = [];
        $title = trim((string)($this->input['title'] ?? ''));
        $tag = strtoupper(trim((string)($this->input['tag'] ?? '')));
        if (mb_strlen($title) < 3 || mb_strlen($title) > 100)
        {
            $errors[] = 'Clan name must be between 3 and 100 characters.';
        }
        if (!preg_match('/^[A-Z0-9_-]{2,24}$/', $tag))
        {
            $errors[] = 'Clan tag must be 2-24 characters and contain only letters, numbers, _ or -.';
        }
        $clanRepo = $this->repository('Warext\\Clans:Clan');
        if ($clanRepo->isTagReserved($tag))
        {
            $errors[] = 'This clan tag is reserved by forum management.';
        }
        if ($clanRepo->isTitleReserved($title))
        {
            $errors[] = 'This clan name is reserved by forum management.';
        }
        if (!$clanRepo->isTagAvailable($tag, 0, $this->existing ? $this->existing->application_id : 0))
        {
            $errors[] = 'This clan tag is already in use or awaiting approval.';
        }
        if (!$clanRepo->isTitleAvailable($title, 0, $this->existing ? $this->existing->application_id : 0))
        {
            $errors[] = 'This clan name is already in use or awaiting approval.';
        }

        $tagColor = $this->normalizeColor((string)($this->input['requested_tag_color'] ?? '#4f46e5'));
        $bannerColor = $this->normalizeColor((string)($this->input['requested_manager_banner_color'] ?? '#805ad5'));
        if (!$tagColor || !$bannerColor)
        {
            $errors[] = 'Tag and banner colors must be valid hexadecimal colors.';
        }

        $maxOwned = (int)($this->app->options()->wxClansMaxOwned ?? 0);
        if ($maxOwned > 0 && $this->repository('Warext\\Clans:Clan')->countOwnedClans($this->user->user_id) >= $maxOwned)
        {
            $errors[] = 'You have reached the maximum number of clans you may own.';
        }

        if (!$this->existing)
        {
            $pending = $this->finder('Warext\\Clans:ClanApplication')
                ->where('application_type','create')
                ->where('user_id',$this->user->user_id)
                ->where('status','pending')
                ->fetchOne();
            if ($pending)
            {
                $errors[] = 'You already have a pending clan creation application.';
            }
        }
        elseif ($this->existing->status !== 'changes_requested')
        {
            $errors[] = 'This clan application can no longer be edited.';
        }

        return !$errors;
    }

    public function save(): \Warext\Clans\Entity\ClanApplication
    {
        if (!$this->validate($errors))
        {
            throw new \XF\PrintableException(implode(' ', $errors));
        }

        $application = $this->existing ?: $this->em()->create('Warext\\Clans:ClanApplication');
        $application->bulkSet([
            'application_type' => 'create',
            'user_id' => $this->user->user_id,
            'title' => trim((string)$this->input['title']),
            'tag' => strtoupper(trim((string)$this->input['tag'])),
            'description' => trim((string)($this->input['description'] ?? '')),
            'category' => trim((string)($this->input['category'] ?? '')),
            'requested_manager_banner' => mb_substr(trim((string)($this->input['requested_manager_banner'] ?? '')), 0, 100),
            'requested_tag_color' => $this->normalizeColor((string)($this->input['requested_tag_color'] ?? '#4f46e5')),
            'requested_manager_banner_color' => $this->normalizeColor((string)($this->input['requested_manager_banner_color'] ?? '#805ad5')),
            'status' => 'pending',
            'decision_date' => 0,
            'decision_user_id' => 0,
            'decision_reason' => ''
        ]);
        if (!$this->existing)
        {
            $application->create_date = \XF::$time;
        }
        $application->save();
        return $application;
    }

    protected function normalizeColor(string $color): string
    {
        $color = strtolower(trim($color));
        if (!preg_match('/^#[0-9a-f]{6}$/', $color))
        {
            return '';
        }
        return $color;
    }
}
