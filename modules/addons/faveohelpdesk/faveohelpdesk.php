<?php
if (!defined("WHMCS")) {
  die("This file cannot be accessed directly");
}

function faveohelpdesk_config()
{
  return [
    'name' => 'Faveo Helpdesk',
    'description' => 'This module replaces the links to internal WHMCS ticketing system to Faveo Help Desk',
    'author' => '<img src="/modules/addons/faveohelpdesk/logo.png" style="width: 75px;" alt="Faveo Help Desk"/>',
    'language' => 'english',
    'version' => '1.0',
    'fields' => [
      'faveoLicense' => [
        'FriendlyName' => 'Faveo License Key',
        'Type' => 'text',
        'Size' => '65',
        'Default' => '',
      ],
      'disableWHMCSTicketing' => [
        'FriendlyName' => 'Disable WHMCS Ticketing',
        'Type' => 'yesno'
      ],
      'disableWHMCSKB' => [
        'FriendlyName' => 'Disable WHMCS Knowledgebase',
        'Type' => 'yesno'
      ],
      'disableWHMCSAnnouncements' => [
        'FriendlyName' => 'Disable WHMCS Announcements',
        'Type' => 'yesno'
      ],
      'faveoSystemURL' => [
        'FriendlyName' => 'Faveo System URL',
        'Type' => 'text',
        'Size' => '65',
        'Default' => '',
        'Description' => '<br/>The URL to your Faveo installation (SSL Recommended) eg. https://www.example.com/public/'
      ],
    ],
  ];
}

function faveohelpdesk_activate($vars)
{
  if (file_exists(ROOTDIR . '/modules/widgets/Support.php')) {
    rename(ROOTDIR . '/modules/widgets/Support.php', ROOTDIR . '/modules/widgets/Support.php.bak');
  }

  return [
    'status' => 'success',
  ];
}

function faveohelpdesk_deactivate()
{
  if (file_exists(ROOTDIR . '/modules/widgets/Support.php.bak')) {
    rename(ROOTDIR . '/modules/widgets/Support.php.bak', ROOTDIR . '/modules/widgets/Support.php');
  }

  return [
    'status' => 'success',
  ];
}