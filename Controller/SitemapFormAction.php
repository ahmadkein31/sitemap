<?php

namespace Ahmad\SitemapMigrate\Controller;

class SitemapFormAction
{
    public function execute( )
    {
        if (isset($_SESSION['login'])) {
            require __DIR__ . '/../view/sitemap.phtml';
            return;
        }

        header('Location: /');
        return;
    }

}