<?php


function wa_header()
{

    // DEPRECATED; left in wa-1.3.css world

    $system = waSystem::getInstance();
    if ($system->getEnv() == 'frontend') {
        return '';
    }
    $root_url = $system->getRootUrl();
    $backend_url = $system->getConfig()->getBackendUrl(true);
    $user = $system->getUser();
    $apps = $user->getApps();
    $current_app = $system->getApp();

    $app_settings_model = new waAppSettingsModel();

    $apps_html = '';
    $applist_class = '';
    $counts = wa()->getStorage()->read('apps-count');
    if (is_array($counts)) {
        $applist_class .= ' counts-cached';
    }
    foreach ($apps as $app_id => $app) {
        if (isset($app['img'])) {
            $img = '<img '.(!empty($app['icon'][96]) ? 'data-src2="'.$root_url.$app['icon'][96].'"' : '').' src="'.$root_url.$app['img'].'" alt="">';
        } else {
            $img = '';
        }

        $count = '';
        $app_url = $backend_url.$app_id.'/';
        if ($counts && isset($counts[$app_id])) {
            if (is_array($counts[$app_id])) {
                $app_url = $counts[$app_id]['url'];
                $n = $counts[$app_id]['count'];
            } else {
                $n = $counts[$app_id];
            }
            if ($n) {
                $count = '<span class="indicator">'.$n.'</span>';
            }
        }
        $apps_html .= '<li id="wa-app-'.$app_id.'"'.($app_id == $current_app ? ' class="selected"':'').'><a href="'.$app_url.'">'.$img.' '.$app['name'].$count.'</a></li>';
    }

    $announcement_model = new waAnnouncementModel();
    $announcements = array();
    if ($current_app != 'webasyst') {
        $data = $announcement_model->getByApps($user->getId(), array_keys($apps), $user['create_datetime']);
        foreach ($data as $row) {
            // show no more than 1 message per application
            if (isset($announcements[$row['app_id']]) && count($announcements[$row['app_id']]) >= 1) {
                continue;
            }
            $announcements[$row['app_id']][] = $row['text'] . ' <span class="hint">' . waDateTime::format('humandatetime', $row['datetime']) . '</span>';
        }
    }
    $announcements_html = '';
    foreach ($announcements as $app_id => $texts) {
        $announcements_html .= '<a href="#" rel="'.$app_id.'" class="wa-announcement-close" title="close">&times;</a><p>';
        $announcements_html .= implode('<br />', $texts);
        $announcements_html .= '</p>';
    }
    if ($announcements_html) {
        $announcements_html = '<div id="wa-announcement">'.$announcements_html.'</div>';
    }

    $logout = _ws('logout');
    $userpic = '<img width="32" height="32" src="'.$user->getPhoto(32).'" alt="">';
    $username = htmlspecialchars(waContactNameField::formatName($user), ENT_QUOTES, 'utf-8');
    $userpic = '<a href="'.$backend_url.'?module=profile">'.$userpic.'</a>';
    $username = '<a href="'.$backend_url.'?module=profile" id="wa-my-username">'.$username.'</a>';

    if ($applist_class) {
        $applist_class = ' class="'.trim($applist_class).'"';
    }

    $company_name = htmlspecialchars($app_settings_model->get('webasyst', 'name', 'Webasyst'), ENT_QUOTES, 'utf-8');
    $company_url = $app_settings_model->get('webasyst', 'url', $system->getRootUrl(true));

    $version = wa()->getVersion('webasyst');

    $strings = array(
        'customize' => _ws('Customize dashboard'),
        'done' => _ws('Done editing'),
        'date' => _ws(waDateTime::date('l')).', '.trim(str_replace(date('Y'), '', waDateTime::format('humandate')), ' ,/'),
    );

    $html = <<<HTML
<script type="text/javascript">var backend_url = "{$backend_url}";</script>
{$announcements_html}
<div id="wa-header">
    <div id="wa-account">
HTML;
    if (wa()->getApp() == 'webasyst') {
        $html .= <<<HTML
        <h3>{$company_name} <a href="{$company_url}" class="wa-frontend-link" target="_blank"><i class="icon16 new-window"></i></a></h3>
        <a class="inline-link" id="show-dashboard-editable-mode" href="{$backend_url}"><b><i>{$strings['customize']}</i></b></a>
        <input id="close-dashboard-editable-mode" type="button" value="{$strings['done']}" style="display: none;">
HTML;
    } else {
        $html .= <<<HTML
        <a href="{$backend_url}" class="wa-dashboard-link"><h3>{$company_name}</h3>
        <span class="gray">{$strings['date']}</span></a>
HTML;
    }
    $html .= <<<HTML
    </div>
    <div id="wa-usercorner" data-user-id="{$user['id']}">
        <div class="profile image32px">
            <div class="image">
                {$userpic}
            </div>
            <div class="details">
                {$username}
                <p class="status"></p>
                <a class="hint" href="{$backend_url}?action=logout">{$logout}</a>
            </div>
        </div>
    </div>
    <div id="wa-applist" {$applist_class}>
        <ul>
            {$apps_html}
            <li>
                <a href="#" id="wa-moreapps"></a>
            </li>
        </ul>
HTML;
    if (wa()->getApp() == 'webasyst') {
        $html .= '<div class="d-dashboard-header-content">
            <div class="d-dashboards-list-wrapper" id="d-dashboards-list-wrapper"></div>
            <div class="d-dashboard-link-wrapper" id="d-dashboard-link-wrapper"><i class="icon10 lock-bw"></i> '._w('Only you can see this dashboard.').'</div>
        </div>';
    }
    if (!$user['timezone']) {
        $attrs = ' data-determine-timezone="1"';
    } else {
        $attrs = '';
    }
    $html .= <<<HTML
    </div>
</div>
<script id="wa-header-js" type="text/javascript" src="{$root_url}wa-content/js/jquery-wa/wa.header.js?{$version}"{$attrs}></script>
HTML;

    /**
     * @event backend_header
     * @return array[string]array $return[%plugin_id%] array of html output
     * @return array[string][string]string $return[%plugin_id%]['header_top'] html output
     * @return array[string][string]string $return[%plugin_id%]['header_bottom'] html output
     */
    $params = array('html' => &$html);
    $results = wa()->event(array('webasyst', 'backend_header'), $params, array('html'));
    foreach($results as $data) {
        if (is_array($data)) {
            $html = ifset($data['header_top'], '').$html.ifset($data['header_bottom'], '');
        } else {
            $html .= (string) $data;
        }
    }

    return $html;
}

function wa_account()
{
    $system = waSystem::getInstance();
    if ($system->getEnv() == 'frontend') {
        return '';
    }
    $root_url = $system->getRootUrl();
    $backend_url = $system->getConfig()->getBackendUrl(true);
    $user = $system->getUser();

    $app_settings_model = new waAppSettingsModel();

    $company_name = htmlspecialchars($app_settings_model->get('webasyst', 'name', 'Webasyst'), ENT_QUOTES, 'utf-8');
    $company_url = $app_settings_model->get('webasyst', 'url', $system->getRootUrl(true));

    $version = wa()->getVersion('webasyst');

    $strings = array(
        'customize' => _ws('Customize dashboard'),
        'done' => _ws('Done editing'),
        'date' => _ws(waDateTime::date('l')).', '.trim(str_replace(date('Y'), '', waDateTime::format('humandate')), ' ,/'),
    );

    $html = <<<HTML
<script type="text/javascript">var backend_url = "{$backend_url}";</script>

                    <!-- TODO: MOVE THIS SOMEWHERE -->
                    <script defer src="/wa-content/js/fontawesome/fontawesome-all.min.js"></script>


    <header id="wa-account">
        <a href="{$backend_url}" class="wa-gradient-overlay-heavy"><h3>{$company_name}</h3></a>
    </header>

<script id="wa-header-js" type="text/javascript" src="{$root_url}wa-content/js/jquery-wa/wa.header.js?{$version}"></script>
HTML;

    /**
     * @event backend_header
     * @return array[string]array $return[%plugin_id%] array of html output
     * @return array[string][string]string $return[%plugin_id%]['header_top'] html output
     * @return array[string][string]string $return[%plugin_id%]['header_bottom'] html output
     */
    $params = array('html' => &$html);
    $results = wa()->event(array('webasyst', 'backend_header'), $params, array('html'));
    foreach($results as $data) {
        if (is_array($data)) {
            $html = ifset($data['header_top'], '').$html.ifset($data['header_bottom'], '');
        } else {
            $html .= (string) $data;
        }
    }

    return $html;
}

function wa_applist()
{
    $system = waSystem::getInstance();
    if ($system->getEnv() == 'frontend') {
        return '';
    }
    $root_url = $system->getRootUrl();
    $backend_url = $system->getConfig()->getBackendUrl(true);
    $user = $system->getUser();
    $apps = $user->getApps();
    $current_app = $system->getApp();

    $app_settings_model = new waAppSettingsModel();

    $apps_html = '';
    $applist_class = '';
    $counts = wa()->getStorage()->read('apps-count');
    if (is_array($counts)) {
        $applist_class .= ' counts-cached';
    }
    foreach ($apps as $app_id => $app) {
        if (isset($app['img'])) {
            $img = '<img '.(!empty($app['icon'][96]) ? 'data-src2="'.$root_url.$app['icon'][96].'"' : '').' src="'.$root_url.$app['img'].'" alt="">';
        } else {
            $img = '';
        }

        $count = '';
        $app_url = $backend_url.$app_id.'/';
        if ($counts && isset($counts[$app_id])) {
            if (is_array($counts[$app_id])) {
                $app_url = $counts[$app_id]['url'];
                $n = $counts[$app_id]['count'];
            } else {
                $n = $counts[$app_id];
            }
            if ($n) {
                $count = '<i class="indicator">'.$n.'</i>';
            }
        }
        $apps_html .= '<li id="wa-app-'.$app_id.'"'.($app_id == $current_app ? ' class="selected"':'').'><a href="'.$app_url.'">'.$img.' <span>'.$app['name'].'</span>'.$count.'</a></li>';
    }

    $announcement_model = new waAnnouncementModel();
    $announcements = array();
    if ($current_app != 'webasyst') {
        $data = $announcement_model->getByApps($user->getId(), array_keys($apps), $user['create_datetime']);
        foreach ($data as $row) {
            // show no more than 1 message per application
            if (isset($announcements[$row['app_id']]) && count($announcements[$row['app_id']]) >= 1) {
                continue;
            }
            $announcements[$row['app_id']][] = $row['text'] . ' <span class="hint">' . waDateTime::format('humandatetime', $row['datetime']) . '</span>';
        }
    }
    $announcements_html = '';
    foreach ($announcements as $app_id => $texts) {
        $announcements_html .= '<a href="#" rel="'.$app_id.'" class="wa-announcement-close" title="close">&times;</a><p>';
        $announcements_html .= implode('<br />', $texts);
        $announcements_html .= '</p>';
    }
    if ($announcements_html) {
        $announcements_html = '<div id="wa-announcement">'.$announcements_html.'</div>';
    }

    $logout = _ws('logout');
    $userpic = '<img src="'.$user->getPhoto(48).'" class="wa-userpic" alt="">';
    $username = htmlspecialchars(waContactNameField::formatName($user), ENT_QUOTES, 'utf-8');
    //$userpic = '<a href="'.$backend_url.'?module=profile">'.$userpic.'<span class="wa-my-status">status <i class="fas fa-ellipsis-h"></i></span></a>';
    $username = '<a href="'.$backend_url.'?module=profile" id="wa-my-username">'.$username.'</a>';

    if ($applist_class) {
        $applist_class = ' class="'.trim($applist_class).'"';
    }

    $company_name = htmlspecialchars($app_settings_model->get('webasyst', 'name', 'Webasyst'), ENT_QUOTES, 'utf-8');
    $company_url = $app_settings_model->get('webasyst', 'url', $system->getRootUrl(true));

    $version = wa()->getVersion('webasyst');

    $strings = array(
        'customize' => _ws('Customize dashboard'),
        'done' => _ws('Done editing'),
        'date' => _ws(waDateTime::date('l')).', '.trim(str_replace(date('Y'), '', waDateTime::format('humandate')), ' ,/'),
    );

    $html = <<<HTML
<script type="text/javascript">var backend_url = "{$backend_url}";</script>
{$announcements_html}

<header id="wa-apps">
    <nav id="wa-applist" {$applist_class}>
        <ul>
            {$apps_html}
            <!-- WA2UI PREVIEW NOTICE: этого элемента не будет. меню будет раскрываться во всей красе просто по :hover с задержкой.<li>
                <a href="#" id="wa-moreapps"></a>
            </li> -->
            <li id="wa-mobile-hamburger">
                <a href="#"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
HTML;

    $html .= <<<HTML
    </nav>
    <aside id="wa-usercorner" data-user-id="{$user['id']}">
        <div class="wa-user-me">
            <a href="#" class="tooltip-bottom" data-tooltip="Set status">
                {$userpic}
            </a>
            <i class="wa-status-indicator"></i>
        </div>
        <!-- <div class="wa-user-stack">
            <a href="#" class="tooltip-bottom" data-tooltip="в Краснодаре!"><img class="wa-userpic" src="/wa-content/img/_dummy_userpics/userpic2.jpg"><i class="wa-status-indicator" style="background: #0081d3;"></i></a>
            <a href="#" class="tooltip-bottom" data-tooltip="гуляю до обеда)"><img class="wa-userpic" src="/wa-content/img/_dummy_userpics/userpic3.jpg"><i class="wa-status-indicator" style="background: #ad2ef6;"></i></a>
            <a href="#" class="tooltip-bottom" data-tooltip="Отпуск до 05.05"><img class="wa-userpic" src="/wa-content/img/_dummy_userpics/userpic6.jpg"><i class="wa-status-indicator" style="background: #ad2ef6;"></i></a>
            <a href="#" class="tooltip-bottom" data-tooltip="Заболел :("><img class="wa-userpic" src="/wa-content/img/_dummy_userpics/userpic5.jpg"><i class="wa-status-indicator" style="background: #a00;"></i></a>
            <a href="#" class="tooltip-bottom" data-tooltip="Онлайн"><img class="wa-userpic" src="/wa-content/img/_dummy_userpics/userpic4.jpg"><i class="wa-status-indicator"></i></a>
        </div> -->
    </aside>
</header>
<div id="wa-apps-dummy"></div>
HTML;

    /**
     * @event backend_header
     * @return array[string]array $return[%plugin_id%] array of html output
     * @return array[string][string]string $return[%plugin_id%]['header_top'] html output
     * @return array[string][string]string $return[%plugin_id%]['header_bottom'] html output
     */
    $params = array('html' => &$html);
    $results = wa()->event(array('webasyst', 'backend_header'), $params, array('html'));
    foreach($results as $data) {
        if (is_array($data)) {
            $html = ifset($data['header_top'], '').$html.ifset($data['header_bottom'], '');
        } else {
            $html .= (string) $data;
        }
    }

    return $html;
}





function wa_url($absolute = false)
{
    return waSystem::getInstance()->getRootUrl($absolute);
}

function wa_backend_url()
{
    return waSystem::getInstance()->getConfig()->getBackendUrl(true);
}
