<?php
$faveo_mysqli = null;

function favehelpdesk_getMySQLiLink()
{
  global $db_host, $db_port, $db_username, $db_password, $db_name, $faveo_mysqli;

  if ($faveo_mysqli) {
    return $faveo_mysqli;
  }

  $faveo_mysqli = mysqli_connect($db_host, $db_username, $db_password, $db_name, $db_port ?: 3306);

  if (mysqli_connect_errno())
  {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
  }

  return $faveo_mysqli;
}

function favehelpdesk_installLicense()
{
  require_once __DIR__ . '/SCRIPT/apl_core_configuration.php';
  require_once __DIR__ . '/SCRIPT/apl_core_functions.php';

  $systemURL = WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value');
  $systemURL = trim($systemURL, '/');
  $parts = parse_url($systemURL);
  $systemURL = "{$parts['scheme']}://{$parts['host']}";
  $settings = WHMCS\Module\Addon\Setting::where('module', 'faveohelpdesk')->pluck('value', 'setting');
  $license = aplInstallLicense($systemURL, null, trim($settings['faveoLicense']), favehelpdesk_getMySQLiLink());
  return $license;
}

function faveohelpdesk_uninstallLicense()
{
  require_once __DIR__ . '/SCRIPT/apl_core_configuration.php';
  require_once __DIR__ . '/SCRIPT/apl_core_functions.php';
  aplUninstallLicense(favehelpdesk_getMySQLiLink());
}

function faveohelpdesk_verifyLicense($force = 0)
{
  // return ['notification_case' => 'notification_license_ok'];
  require_once __DIR__ . '/SCRIPT/apl_core_configuration.php';
  require_once __DIR__ . '/SCRIPT/apl_core_functions.php';

  $settings = WHMCS\Module\Addon\Setting::where('module', 'faveohelpdesk')->pluck('value', 'setting');
  if (!$settings['faveoLicense']) {
    faveohelpdesk_uninstallLicense();
    return;
  }

  $license = aplVerifyLicense(favehelpdesk_getMySQLiLink(), $force);
  if (
    $license['notification_case'] == 'notification_license_corrupted'
    && $license['notification_text'] == 'License is not installed yet or corrupted.'
  ) {
    $license = favehelpdesk_installLicense();
  }
  return $license;
}

function faveohelpdesk_urls()
{
  return [
    'api' => [
      'urlCheck' => 'api/v1/helpdesk/url',
    ],
    'admin' => [
      'viewTicket' => 'tickets?show=mytickets',
      'openTicket' => 'newticket',
      'predefiendReplies' => 'canned/list',
      'knowledgebase' => 'category',
      'announcement' => '',
    ],
    'client' => [
      'viewTicket' => 'mytickets',
      'openTicket' => 'create-ticket',
      'knowledgebase' => 'knowledgebase',
      'announcement' => '',
    ],
  ];
}

function faveohelpdesk_isLoggedIn()
{
  return (WHMCS\Session::get('uid') && WHMCS\Session::get('upw'));
}

add_hook('AdminAreaPage', 100, function($vars) {
  $license = faveohelpdesk_verifyLicense();
  if ($license['notification_case'] != 'notification_license_ok') return [];

  $urls = faveohelpdesk_urls();

  $settings = WHMCS\Module\Addon\Setting::where('module', 'faveohelpdesk')->pluck('value', 'setting');
  $settings['faveoSystemURL'] = trim($settings['faveoSystemURL'], '/') . '/';

  if ($settings['faveoSystemURL']) {
    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['admin']['openTicket']) {
      $jquerycode .= "\$('a[href\$=\"supporttickets.php?action=open\"]')
                        .attr('target', '_blank')
                        .attr('href', '{$settings['faveoSystemURL']}{$urls['admin']['openTicket']}');";
      $jquerycode .= "\$('a#Menu-Setup-Support').parent('li').remove();";
      $jquerycode .= "\$('a#Menu-Support-Support_Overview').parent('li').remove();";
      $jquerycode .= "\$('a#Menu-Support-Support_Tickets').parent('li').remove();";
      $jquerycode .= "\$('a#Menu-Support-Predefined_Replies').parent('li').remove();";
      $jquerycode .= "\$('span.header:contains(\"Filter Tickets\")').remove();";
      $jquerycode .= "\$('form[action=\"supporttickets.php\"]').remove();";
      $jquerycode .= "\$('a:contains(\"Ticket(s) Awaiting Reply\")').remove();";
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      $jquerycode .= "\$('a[href\$=\"supporttickets.php?action=open\"]').parent('li').remove();";
      $jquerycode .= "\$('a#Menu-Setup-Support').parent('li').remove();";
      $jquerycode .= "\$('a#Menu-Support-Support_Overview').parent('li').remove();";
      $jquerycode .= "\$('a#Menu-Support-Support_Tickets').parent('li').remove();";
      $jquerycode .= "\$('a#Menu-Support-Predefined_Replies').parent('li').remove();";
      $jquerycode .= "\$('span.header:contains(\"Filter Tickets\")').remove();";
      $jquerycode .= "\$('form[action=\"supporttickets.php\"]').remove();";
      $jquerycode .= "\$('a:contains(\"Ticket(s) Awaiting Reply\")').remove();";
    }

    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['admin']['viewTicket']) {
      $jquerycode .= "\$('a[href\$=\"supporttickets.php\"]')
                        .attr('target', '_blank')
                        .attr('href', '{$settings['faveoSystemURL']}{$urls['admin']['viewTicket']}')
                        .parent('li')
                        .removeClass('expand')
                        .children('ul').remove();";
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      $jquerycode .= "\$('a[href\$=\"supporttickets.php\"]')
                        .parent('li')
                        .removeClass('expand')
                        .children('ul').remove();";
    }

    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['admin']['predefiendReplies']) {
      $jquerycode .= "\$('a[href\$=\"supportticketpredefinedreplies.php\"]').attr('target', '_blank').attr('href', '{$settings['faveoSystemURL']}{$urls['admin']['predefiendReplies']}');";
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      $jquerycode .= "\$('a[href\$=\"supportticketpredefinedreplies.php\"]').parent('li').remove();";
    }

    if ($settings['disableWHMCSKB'] == 'on' && $urls['admin']['knowledgebase']) {
      $jquerycode .= "\$('a[href\$=\"supportkb.php\"]').attr('target', '_blank').attr('href', '{$settings['faveoSystemURL']}{$urls['admin']['knowledgebase']}');";
    } elseif ($settings['disableWHMCSKB'] == 'on') {
      $jquerycode .= "\$('a[href\$=\"supportkb.php\"]').parent('li').remove();";
    }

    if ($settings['disableWHMCSAnnouncements'] == 'on' && $urls['admin']['announcement']) {
      $jquerycode .= "\$('a[href\$=\"supportannouncements.php\"]').attr('target', '_blank').attr('href', '{$settings['faveoSystemURL']}{$urls['admin']['announcement']}');";
    } elseif ($settings['disableWHMCSAnnouncements'] == 'on')  {
      $jquerycode .= "\$('a[href\$=\"supportannouncements.php\"]').parent('li').remove();";
    }
  }

  //URL Check
  if (strpos($_SERVER['SCRIPT_NAME'], 'configaddonmods.php') !== FALSE) {
    $jquerycode .= "
      function installationCheck()
      {
        let url = \$(this).val().trim('/') + '/';
        \$('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').parent().html(
          \$('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]')
            .parent().html()
            .replace('✓', '')
            .replace('x', '')
        );
        \$('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').val(url);
        \$.get('/modules/addons/faveohelpdesk/search.php', {url: url}, function (data) {
          if (data.result == 'success') {
            \$('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').after('✓');
            \$('input[name=\"msave_faveohelpdesk\"]').prop('disabled', 0);
          } else {
            \$('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').after('x');
            \$('input[name=\"msave_faveohelpdesk\"]').prop('disabled', 1);
          }
          \$('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').on('change', installationCheck);
        }, 'json');
        return true;
      }

      \$('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').on('change', installationCheck);";
  }

  if ($settings['disableWHMCSTicketing'] == 'on' && strpos($_SERVER['SCRIPT_NAME'], 'index.php') !== FALSE) {
    $jquerycode .= "
      if (\$('div.status-badge-pink')) {
        \$('div.status-badge-pink').parent().remove();
      }
      if (\$('div.stats').find('a:last')) {
        \$('div.stats').find('a:last').remove()
      }
    ";
  }

  return ['jquerycode' => $vars['jquerycode'] . $jquerycode];
});


add_hook('ClientAreaPrimaryNavbar', 100, function(WHMCS\View\Menu\Item $primaryNavbar)
{
  $license = faveohelpdesk_verifyLicense();
  if ($license['notification_case'] != 'notification_license_ok') return [];

  $urls = faveohelpdesk_urls();

  $settings = WHMCS\Module\Addon\Setting::where('module', 'faveohelpdesk')->pluck('value', 'setting');
  $settings['faveoSystemURL'] = trim($settings['faveoSystemURL'], '/') . '/';

  if ($settings['faveoSystemURL']) {
    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['client']['openTicket'] && faveohelpdesk_isLoggedIn()) {
      $openTicket = $primaryNavbar->getChild("Open Ticket");
      if (!$openTicket) {
        $openTicket = $primaryNavbar->addChild(
          'Open Ticket',
          [
            'name' => 'Open Ticket',
            'label' => Lang::trans('navopenticket'),
            'order' => 99,
          ]
        );
      }
      $openTicket->setAttribute('target', '_blank')
        ->setUri($settings['faveoSystemURL'] . $urls['client']['openTicket']);
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      if ($primaryNavbar->getChild("Open Ticket")) {
        $primaryNavbar->removeChild("Open Ticket");
      }
    }

    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['client']['viewTicket'] && faveohelpdesk_isLoggedIn()) {
      $support = $primaryNavbar->getChild("Support");
      if (!$support) {
        $support = $primaryNavbar->addChild(
          'Support',
          [
            'name' => 'Support',
            'label' => Lang::trans('navsupport'),
            'order' => 89,
          ]
        );
      }

      $tickets = $support->getChild("Tickets");
      if (!$tickets) {
        $tickets = $support->addChild(
          'Tickets',
          [
            'name' => 'Tickets',
            'label' => Lang::trans('navtickets'),
            'order' => 99
          ]
        );
      }

      $tickets->setAttribute('target', '_blank')
        ->setUri($settings['faveoSystemURL'] . $urls['client']['viewTicket']);
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      if ($primaryNavbar->getChild("Support") && $primaryNavbar->getChild("Support")->getChild('Tickets')) {
        $primaryNavbar->getChild("Support")->removeChild('Tickets');
      }
    }

    if ($settings['disableWHMCSKB'] == 'on' && $urls['client']['knowledgebase']) {
      $support = faveohelpdesk_isLoggedIn() ? $primaryNavbar->getChild("Support") : $primaryNavbar;
      if (!$support) {
        $support = $primaryNavbar->addChild(
          'Support',
          [
            'name' => 'Support',
            'label' => Lang::trans('navsupport'),
            'order' => 89,
          ]
        );
      }

      $knowledgebase = $support->getChild("Knowledgebase");
      if (!$knowledgebase) {
        $knowledgebase = $support->addChild(
          'Knowledgebase',
          [
            'name' => 'Knowledgebase',
            'label' => Lang::trans('knowledgebasetitle'),
            'order' => 99
          ]
        );
      }

      $knowledgebase->setAttribute('target', '_blank')
        ->setUri($settings['faveoSystemURL'] . $urls['client']['knowledgebase']);
    } elseif ($settings['disableWHMCSKB'] == 'on') {
      if ($primaryNavbar->getChild("Support") && $primaryNavbar->getChild("Support")->getChild('Knowledgebase')) {
        $primaryNavbar->getChild("Support")->removeChild('Knowledgebase');
      } elseif ($primaryNavbar->getChild('Knowledgebase')) {
        $primaryNavbar->removeChild('Knowledgebase');
      }
    }

    if ($settings['disableWHMCSAnnouncements'] == 'on' && $urls['client']['announcement']) {
      $support = faveohelpdesk_isLoggedIn() ? $primaryNavbar->getChild("Support") : $primaryNavbar;
      if (!$support) {
        $support = $primaryNavbar->addChild(
          'Support',
          [
            'name' => 'Support',
            'label' => Lang::trans('navsupport'),
            'order' => 89,
          ]
        );
      }

      $announcement = $support->getChild("Announcements");
      if (!$announcement) {
        $announcement = $support->addChild(
          'Announcements',
          [
            'name' => 'Announcements',
            'label' => Lang::trans('announcementstitle'),
            'order' => 99
          ]
        );
      }

      $announcement->setAttribute('target', '_blank')
        ->setUri($settings['faveoSystemURL'] . $urls['client']['announcement']);
    } elseif ($settings['disableWHMCSAnnouncements'] == 'on')  {
      if ($primaryNavbar->getChild("Support") && $primaryNavbar->getChild("Support")->getChild('Announcements')) {
        $primaryNavbar->getChild("Support")->removeChild('Announcements');
      } elseif ($primaryNavbar->getChild('Announcements')) {
        $primaryNavbar->removeChild('Announcements');
      }
    }
  }
});

add_hook('ClientAreaSecondarySidebar', 100, function(WHMCS\View\Menu\Item $secondarySidebar) {
  $license = faveohelpdesk_verifyLicense();
  if ($license['notification_case'] != 'notification_license_ok') return [];

  $urls = faveohelpdesk_urls();

  $settings = WHMCS\Module\Addon\Setting::where('module', 'faveohelpdesk')->pluck('value', 'setting');
  $settings['faveoSystemURL'] = trim($settings['faveoSystemURL'], '/') . '/';

  if ($settings['faveoSystemURL']) {
    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['client']['openTicket']) {
      $support = $secondarySidebar->getChild("Support");
      if ($support) {
        $openTicket = $support->getChild("Open Ticket");
        if (!$openTicket) {
          $openTicket = $support->addChild(
            'Open Ticket',
            [
              'name' => 'Open Ticket',
              'label' => Lang::trans('navopenticket'),
              'order' => 99,
              'icon' => 'fas fa-comments fa-fw',
            ]
          );
        }

        $openTicket->setAttribute('target', '_blank')
          ->setUri($settings['faveoSystemURL'] . $urls['client']['openTicket']);
      }
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      if ($secondarySidebar->getChild("Support") && $secondarySidebar->getChild("Support")->getChild('Open Ticket')) {
        $secondarySidebar->getChild("Support")->removeChild('Open Ticket');
      }
    }

    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['client']['viewTicket']) {
      $support = $secondarySidebar->getChild("Support");
      if ($support) {
        $tickets = $support->getChild("Support Tickets");
        if (!$tickets) {
          $tickets = $support->addChild(
            'Support Tickets',
            [
              'name' => 'Support Tickets',
              'label' => Lang::trans('clientareanavsupporttickets'),
              'order' => 1,
              'icon' => 'fas fa-ticket-alt fa-fw',
            ]
          );
        }

        $tickets->setAttribute('target', '_blank')
          ->setUri($settings['faveoSystemURL'] . $urls['client']['viewTicket']);
      }
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      if ($secondarySidebar->getChild("Support") && $secondarySidebar->getChild("Support")->getChild('Support Tickets')) {
        $secondarySidebar->getChild("Support")->removeChild('Support Tickets');
      }
    }

    if ($settings['disableWHMCSKB'] == 'on' && $urls['client']['knowledgebase']) {
      $support = $secondarySidebar->getChild("Support");
      if ($support) {
        $knowledgebase = $support->getChild("Knowledgebase");
        if (!$knowledgebase) {
          $knowledgebase = $support->addChild(
            'Knowledgebase',
            [
              'name' => 'Knowledgebase',
              'label' => Lang::trans('knowledgebasetitle'),
              'order' => 3,
              'icon' => 'fas fa-info-circle fa-fw',
            ]
          );
        }

        $knowledgebase->setAttribute('target', '_blank')
          ->setUri($settings['faveoSystemURL'] . $urls['client']['knowledgebase']);
      }
    } elseif ($settings['disableWHMCSKB'] == 'on') {
      if ($secondarySidebar->getChild("Support") && $secondarySidebar->getChild("Support")->getChild('Knowledgebase')) {
        $secondarySidebar->getChild("Support")->removeChild('Knowledgebase');
      }
    }

    if ($settings['disableWHMCSAnnouncements'] == 'on' && $urls['client']['announcement']) {
      $support = $secondarySidebar->getChild("Support");
      if ($support) {
        $announcement = $support->getChild("Announcements");
        if (!$announcement) {
          $announcement = $support->addChild(
            'Announcements',
            [
              'name' => 'Announcements',
              'label' => Lang::trans('announcementstitle'),
              'order' => 2,
              'icon' => 'fas fa-list fa-fwfw',
            ]
          );
        }

        $announcement->setAttribute('target', '_blank')
          ->setUri($settings['faveoSystemURL'] . $urls['client']['announcement']);
      }
    } elseif ($settings['disableWHMCSAnnouncements'] == 'on')  {
      if ($secondarySidebar->getChild("Support") && $secondarySidebar->getChild("Support")->getChild('Announcements')) {
        $secondarySidebar->getChild("Support")->removeChild('Announcements');
      }
    }
  }
});

add_hook('ClientAreaHeadOutput', 1, function($vars) {
  $license = faveohelpdesk_verifyLicense();
  if ($license['notification_case'] != 'notification_license_ok') return [];

  $urls = faveohelpdesk_urls();

  $settings = WHMCS\Module\Addon\Setting::where('module', 'faveohelpdesk')->pluck('value', 'setting');
  $settings['faveoSystemURL'] = trim($settings['faveoSystemURL'], '/') . '/';

  if ($settings['faveoSystemURL']) {
    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['client']['openTicket']) {
      return "
        <script type='text/javascript'>
          $(document).ready(function () {
            $('a[href\$=\"submitticket.php\"]')
              .attr('href', '{$settings['faveoSystemURL']}{$urls['client']['openTicket']}')
              .attr('target', '_blank');
          });
        </script>
      ";
    }
  }
});

add_hook('AfterCronJob', 100, function() {
  faveohelpdesk_verifyLicense(1);
});