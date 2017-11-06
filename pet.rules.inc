<?php

/**
 * @file
 * Rules integration for the Pet module.
 *
 * @todo: review/update.
 */

use Drupal\Core\Url;
use Drupal\pet\Entity\Pet;
use Drupal\pet\Utility\PetHelper;

/**
 * Implements hook_rules_action_info().
 */
function pet_rules_action_info() {
  $actions = [
    'pet_action_send_pet' => [
      'label' => $this->t('Send Pet mail'),
      'group' => $this->t('Previewable email templates'),
      'parameter' => [
        'pet_name' => [
          'type' => 'text',
          'label' => $this->t('The previewable email template to use'),
          'options list' => 'pet_pet_list',
          'description' => $this->t('The template that will be sent for this action. You can see the full list or add a new one from <a href="@url">this page</a>.', ['@url' => Url::fromRoute('pet.list')]),
        ],
        'to_text' => [
          'type' => 'text',
          'label' => $this->t('Recipient(s) (for sending to a fixed set of email address(es))'),
          'description' => $this->t('The e-mail address or addresses where the message will be sent to. The formatting of this string must comply with RFC 2822. Either this or the variable recipient below must be provided.'),
          'optional' => TRUE,
          'default value' => NULL,
        ],
        'to_account' => [
          'type' => 'user',
          'label' => $this->t('Recipient (for sending to a user provided by a Rules event)'),
          'description' => $this->t('Send mail to address on this account. Either this or the fixed recipient(s) above must be provided. If both are provided, this overrides the fixed list.'),
          'optional' => TRUE,
          'default value' => NULL,
        ],
        'account_subs' => [
          'type' => 'user',
          'label' => $this->t('User for token substitutions (if any)'),
          'description' => $this->t('If your template includes user tokens, this user will be used for them.'),
          'optional' => TRUE,
          'default value' => NULL,
        ],
        'node_subs' => [
          'type' => 'node',
          'label' => $this->t('Node for token substitutions (if any)'),
          'description' => $this->t('If your template includes node tokens, this node will be used for them.'),
          'optional' => TRUE,
          'default value' => NULL,
        ],
      ],
    ],
  ];

  return $actions;
}

/**
 * Callback for eponymous rules action.
 */
function pet_action_send_pet($pet_name, $to_text, $to_account, $account_subs, $node_subs, $settings) {
  // TODO: check $pet_name is (int).
  $pet = Pet::load($pet_name);

  // Resolve the recipient.
  if (isset($to_account)) {
    $pet_to = $to_account->getEmail();
  }
  elseif (isset($to_text)) {
    $pet_to = $to_text;
  }
  else {
    \Drupal::logger('pet')->error('Mail send using %name PET failed. No recipient provided.', ['%name' => $pet_name]);
    return;
  }

  $params = [
    'pet_from' => \Drupal::config()->get('site_mail'),
    'pet_to' => $pet_to,
    'pet_uid' => isset($account_subs) ? $account_subs->uid : NULL,
    'pet_nid' => isset($node_subs) ? $node_subs->nid : NULL,
  ];

  PetHelper::sendSingleMail($pet, $params);
}

/**
 * Return list of all Pets for rules configuration.
 */
function pet_pet_list() {
  $list = [];

  foreach (Pet::loadMultiple() as $pet) {
    $list[$pet->name] = $pet->title;
  }

  return $list;
}
