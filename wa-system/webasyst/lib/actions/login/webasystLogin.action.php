<?php

class webasystLoginAction extends waLoginAction
{

    public function execute()
    {
        $this->view->assign('title', $this->getTitle());
        $this->view->assign('title_style', $this->getTitleStyle());

        $this->view->setOptions(array('left_delimiter' => '{', 'right_delimiter' => '}'));
        if ($this->template === null) {
            if (waRequest::isMobile()) {
                $this->setLayout(null);
                $this->template = 'LoginMobile.html';
            } else {
                $this->template = 'Login.html';
            }
            $template_file = wa()->getDataPath('templates/'.$this->template, false, 'webasyst');
            if (file_exists($template_file)) {
                $this->template = 'file:'.$template_file;
            } else {
                $this->template = wa()->getAppPath('templates/actions/login/', 'webasyst') . $this->template;
            }
        }
        $this->view->assign('login', waRequest::post('login', $this->getStorage()->read('auth_login')));
        parent::execute();
        if ($this->layout) {
            $this->layout->assign('error', $this->view->getVars('error'));
        }

        $ref = waRequest::server('HTTP_REFERER');
        if(waRequest::get('back_to') && $ref) {
            $this->getStorage()->write('login_back_on_cancel', $ref);
        } else if (!$ref) {
            $this->getStorage()->remove('login_back_on_cancel');
        }
        $this->view->assign('back_on_cancel', wa()->getStorage()->read('login_back_on_cancel'));
    }

    protected function saveReferer()
    {

    }

    protected function checkAuthConfig()
    {

    }

    protected function afterAuth() {
        $this->getStorage()->remove('auth_login');
        $redirect = $this->getConfig()->getCurrentUrl();
        $backend_url = $this->getConfig()->getBackendUrl(true);
        if (!$redirect || $redirect === $backend_url)
        {
            $redirect = $this->getUser()->getLastPage();
        }
        if (!$redirect || substr($redirect, 0, strlen($backend_url) + 1) == $backend_url.'?')
        {
            $redirect = $backend_url;
        }
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $ip_true = 0;
        $user_true = 0;
        $model = new waUserModel();
        $result = $model->query("SELECT * FROM wa_login_ip");
        foreach ($result as $row)
        {
            $users = unserialize($row['users']);
            if (in_array($this->getUser()->get('id'), $users))
            {
                $user_true = 1;
                if ($ip == $row['ip'])
                {
                    $ip_true = 1;
                    // var_dump($ip_true);
                }
            }
        }
        wa()->getUser()->setSettings('webasyst', 'backend_url', $this->getConfig()->getHostUrl().$backend_url);
        if ($ip_true == 0 && $user_true == 0)
            $this->redirect(array('url' => $redirect));
        if ($ip_true == 0 && $user_true == 1)
            $this->getUser()->logout();
        if ($ip_true == 1 && $user_true == 1)
            $this->redirect(array('url' => $redirect));
        //$this->redirect(array('url' => $redirect));
    }

    public function getTitle()
    {
        if ( ( $title = $this->getConfig()->getOption('login_form_title'))) {
            return waLocale::fromArray($title);
        }
        return wa()->getSetting('name', 'Webasyst', 'webasyst');
    }

    public function getTitleStyle()
    {
        return $this->getConfig()->getOption('login_form_title_style');
    }
}

// EOF