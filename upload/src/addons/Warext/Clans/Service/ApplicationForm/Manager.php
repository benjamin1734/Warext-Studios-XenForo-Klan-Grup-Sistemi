<?php

namespace Warext\Clans\Service\ApplicationForm;

use XF\Service\AbstractService;

class Manager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function save(?\Warext\Clans\Entity\ClanApplicationField $field, array $input, int $actorUserId): \Warext\Clans\Entity\ClanApplicationField
    {
        if (!$field)
        {
            $maxFields = (int)($this->app->options()->wxClansMaxFields ?? 20);
            if ($maxFields > 0)
            {
                $fieldCount = $this->finder('Warext\\Clans:ClanApplicationField')
                    ->where('clan_id', $this->clan->clan_id)
                    ->total();
                if ($fieldCount >= $maxFields)
                {
                    throw new \XF\PrintableException('This clan has reached the maximum number of application fields.');
                }
            }

            $field = $this->em()->create('Warext\\Clans:ClanApplicationField');
            $field->clan_id = $this->clan->clan_id;
        }
        elseif ($field->clan_id !== $this->clan->clan_id)
        {
            throw new \LogicException('Application field does not belong to this clan.');
        }

        $title = trim((string)($input['title'] ?? ''));
        $type = (string)($input['field_type'] ?? 'text');
        if ($title === '' || mb_strlen($title) > 100)
        {
            throw new \XF\PrintableException('Field title is required and must be 100 characters or less.');
        }
        if (!in_array($type, ['text','textarea','select','checkbox','number','date'], true))
        {
            throw new \XF\PrintableException('Invalid field type.');
        }

        $fieldKey = trim((string)($input['field_key'] ?? ''));
        if ($fieldKey === '')
        {
            $fieldKey = 'field_' . substr(md5($title . microtime(true)), 0, 10);
        }
        $fieldKey = preg_replace('/[^a-z0-9_]/', '_', strtolower($fieldKey));

        $options = [];
        if (in_array($type, ['select', 'checkbox'], true))
        {
            foreach (preg_split('/\r\n|\r|\n/', (string)($input['options_text'] ?? '')) as $option)
            {
                $option = trim($option);
                if ($option !== '')
                {
                    $options[] = mb_substr($option, 0, 100);
                }
            }
            $options = array_values(array_unique($options));
            if (count($options) > 50)
            {
                throw new \XF\PrintableException('Select and checkbox fields may contain at most 50 options.');
            }
            if (!$options)
            {
                throw new \XF\PrintableException('Select and checkbox fields require at least one option.');
            }
        }

        $field->bulkSet([
            'field_key' => mb_substr($fieldKey, 0, 50),
            'title' => $title,
            'field_type' => $type,
            'field_options' => $options,
            'required' => !empty($input['required']),
            'display_order' => max(1, (int)($input['display_order'] ?? 100)),
            'active' => isset($input['active']) ? (bool)$input['active'] : true
        ]);
        $field->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actorUserId, 'application_field_saved', ['field_id' => $field->field_id, 'title' => $field->title], 'clan_application_field', $field->field_id);
        return $field;
    }

    public function delete(\Warext\Clans\Entity\ClanApplicationField $field, int $actorUserId): void
    {
        if ($field->clan_id !== $this->clan->clan_id)
        {
            throw new \LogicException('Application field does not belong to this clan.');
        }
        $fieldId = $field->field_id;
        $field->delete();
        $this->db()->delete('xf_wx_clan_application_answer', 'field_id = ?', $fieldId);
        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actorUserId, 'application_field_deleted', ['field_id' => $fieldId], 'clan_application_field', $fieldId);
    }
}
