<?php
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
      'knowledgebase' => 'category-list',
      'announcement' => '',
    ],
  ];
}

add_hook('AdminAreaPage', 100, function($vars) {
  // file_put_contents(__DIR__.'/AdminAreaPage_'.time(), json_encode($_SERVER));
  // file_put_contents(__DIR__.'/AdminAreaPageDefinedVars_'.time(), json_encode(get_defined_vars()));

  $urls = faveohelpdesk_urls();

  $settings = WHMCS\Module\Addon\Setting::where('module', 'faveohelpdesk')->pluck('value', 'setting');
  $settings['faveoSystemURL'] = trim($settings['faveoSystemURL'], '/') . '/';

  if ($settings['faveoSystemURL']) {
    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['admin']['openTicket']) {
      $jquerycode .= "$('a[href$=\"supporttickets.php?action=open\"]')
                        .attr('target', '_blank')
                        .attr('href', '{$settings['faveoSystemURL']}{$urls['admin']['openTicket']}');";
      $jquerycode .= "$('a#Menu-Setup-Support').parent('li').remove();";
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      $jquerycode .= "$('a[href$=\"supporttickets.php?action=open\"]').parent('li').remove();";
      $jquerycode .= "$('a#Menu-Setup-Support').parent('li').remove();";
    }

    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['admin']['viewTicket']) {
      $jquerycode .= "$('a[href$=\"supporttickets.php\"]')
                        .attr('target', '_blank')
                        .attr('href', '{$settings['faveoSystemURL']}{$urls['admin']['viewTicket']}')
                        .parent('li')
                        .removeClass('expand')
                        .children('ul').remove();";
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      $jquerycode .= "$('a[href$=\"supporttickets.php\"]')
                        .parent('li')
                        .removeClass('expand')
                        .children('ul').remove();";
    }

    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['admin']['predefiendReplies']) {
      $jquerycode .= "$('a[href$=\"supportticketpredefinedreplies.php\"]').attr('target', '_blank').attr('href', '{$settings['faveoSystemURL']}{$urls['admin']['predefiendReplies']}');";
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      $jquerycode .= "$('a[href$=\"supportticketpredefinedreplies.php\"]').parent('li').remove();";
    }

    if ($settings['disableWHMCSKB'] == 'on' && $urls['admin']['knowledgebase']) {
      $jquerycode .= "$('a[href$=\"supportkb.php\"]').attr('target', '_blank').attr('href', '{$settings['faveoSystemURL']}{$urls['admin']['knowledgebase']}');";
    } elseif ($settings['disableWHMCSKB'] == 'on') {
      $jquerycode .= "$('a[href$=\"supportkb.php\"]').parent('li').remove();";
    }

    if ($settings['disableWHMCSAnnouncements'] == 'on' && $urls['admin']['announcement']) {
      $jquerycode .= "$('a[href$=\"supportannouncements.php\"]').attr('target', '_blank').attr('href', '{$settings['faveoSystemURL']}{$urls['admin']['announcement']}');";
    } elseif ($settings['disableWHMCSAnnouncements'] == 'on')  {
      $jquerycode .= "$('a[href$=\"supportannouncements.php\"]').parent('li').remove();";
    }
  }

  //URL Check
  if (strpos($_SERVER['SCRIPT_NAME'], 'configaddonmods.php') !== FALSE) {
    $jquerycode .= "
function installationCheck()
{
  let url = \$(this).val().trim('/') + '/';
  console.log('url: ', url);
  $('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').parent().html(
    $('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]')
      .parent().html()
      .replace('✓', '')
      .replace('x', '')
  );
  $('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').val(url);
  $.get('/modules/addons/faveohelpdesk/search.php', {url: url}, function (data) {
    console.log('data: ', data.result);
    if (data.result == 'success') {
      $('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').after('✓');
      $('input[name=\"msave_faveohelpdesk\"]').prop('disabled', 0);
    } else {
      $('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').after('x');
      $('input[name=\"msave_faveohelpdesk\"]').prop('disabled', 1);
    }
    $('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').on('change', installationCheck);
  }, 'json');
  return true;
}

$('input[name=\"fields[faveohelpdesk][faveoSystemURL]\"]').on('change', installationCheck);";
  }

  return ['jquerycode' => $vars['jquerycode'] . $jquerycode];
});


add_hook('ClientAreaPrimaryNavbar', 100, function(WHMCS\View\Menu\Item $primaryNavbar)
{
  $urls = faveohelpdesk_urls();

  $settings = WHMCS\Module\Addon\Setting::where('module', 'faveohelpdesk')->pluck('value', 'setting');
  $settings['faveoSystemURL'] = trim($settings['faveoSystemURL'], '/') . '/';

  if ($settings['faveoSystemURL']) {
    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['client']['openTicket']) {
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

    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['client']['viewTicket']) {
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
      }
    }

    if ($settings['disableWHMCSAnnouncements'] == 'on' && $urls['client']['announcement']) {
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
      }
    }
  }
});

add_hook('ClientAreaSecondarySidebar', 100, function(WHMCS\View\Menu\Item $secondarySidebar) {
  $urls = faveohelpdesk_urls();

  $settings = WHMCS\Module\Addon\Setting::where('module', 'faveohelpdesk')->pluck('value', 'setting');
  $settings['faveoSystemURL'] = trim($settings['faveoSystemURL'], '/') . '/';

  if ($settings['faveoSystemURL']) {
    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['client']['openTicket']) {
      $support = $secondarySidebar->getChild("Support");
      if (!$support) {
        $support = $secondarySidebar->addChild(
          'Support',
          [
            'name' => 'Support',
            'label' => Lang::trans('navsupport'),
            'order' => 1,
            'icon' => 'far fa-life-ring',
          ]
        );
      }

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

    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      if ($secondarySidebar->getChild("Support") && $secondarySidebar->getChild("Support")->getChild('Open Ticket')) {
        $secondarySidebar->getChild("Support")->removeChild('Open Ticket');
      }
    }

    if ($settings['disableWHMCSTicketing'] == 'on' && $urls['client']['viewTicket']) {
      $support = $secondarySidebar->getChild("Support");
      if (!$support) {
        $support = $secondarySidebar->addChild(
          'Support',
          [
            'name' => 'Support',
            'label' => Lang::trans('navsupport'),
            'order' => 1,
            'icon' => 'far fa-life-ring',
          ]
        );
      }

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
    } elseif ($settings['disableWHMCSTicketing'] == 'on') {
      if ($secondarySidebar->getChild("Support") && $secondarySidebar->getChild("Support")->getChild('Support Tickets')) {
        $secondarySidebar->getChild("Support")->removeChild('Support Tickets');
      }
    }

    if ($settings['disableWHMCSKB'] == 'on' && $urls['client']['knowledgebase']) {
      $support = $secondarySidebar->getChild("Support");
      if (!$support) {
        $support = $secondarySidebar->addChild(
          'Support',
          [
            'name' => 'Support',
            'label' => Lang::trans('navsupport'),
            'order' => 1,
            'icon' => 'far fa-life-ring',
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
            'order' => 3,
            'icon' => 'fas fa-info-circle fa-fw',
          ]
        );
      }

      $knowledgebase->setAttribute('target', '_blank')
        ->setUri($settings['faveoSystemURL'] . $urls['client']['knowledgebase']);
    } elseif ($settings['disableWHMCSKB'] == 'on') {
      if ($secondarySidebar->getChild("Support") && $secondarySidebar->getChild("Support")->getChild('Knowledgebase')) {
        $secondarySidebar->getChild("Support")->removeChild('Knowledgebase');
      }
    }

    if ($settings['disableWHMCSAnnouncements'] == 'on' && $urls['client']['announcement']) {
      $support = $secondarySidebar->getChild("Support");
      if (!$support) {
        $support = $secondarySidebar->addChild(
          'Support',
          [
            'name' => 'Support',
            'label' => Lang::trans('navsupport'),
            'order' => 1,
            'icon' => 'far fa-life-ring',
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
            'order' => 2,
            'icon' => 'fas fa-list fa-fwfw',
          ]
        );
      }

      $announcement->setAttribute('target', '_blank')
        ->setUri($settings['faveoSystemURL'] . $urls['client']['announcement']);
    } elseif ($settings['disableWHMCSAnnouncements'] == 'on')  {
      if ($secondarySidebar->getChild("Support") && $secondarySidebar->getChild("Support")->getChild('Announcements')) {
        $secondarySidebar->getChild("Support")->removeChild('Announcements');
      }
    }
  }
});