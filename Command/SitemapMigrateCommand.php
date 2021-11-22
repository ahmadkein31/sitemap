<?php

namespace Ahmad\SitemapMigrate\Command;

use Snowdog\DevTest\Core\Database;
use Snowdog\DevTest\Core\Migration;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Ahmad\SitemapMigrate\Model\SitemapManager;
use Snowdog\DevTest\Model\UserManager;

class SitemapMigrateCommand
{

    /**
     * @var Migration
     */
    private $migration;
    /**
     * @var QuestionHelper
     */
    private $helper;
    /**
     * @var UserManager
     */
    private $userManager;
    /**
     * @var Database
     */
    private $database;
    /**
     * @var SitemapManager
     */
    private $sitemapManager;

    public function __construct(
        Migration $migration, 
        QuestionHelper $helper, 
        UserManager $userManager,
        SitemapManager $sitemapManager
    ) {
        $this->migration = $migration;
        $this->helper = $helper;
        $this->userManager = $userManager;
        $this->sitemapManager = $sitemapManager;
    }

    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        $fileExtensionsAllowed = ['csv']; // These will be the only file extensions allowed 

        if($input->getArgument('filename') && $input->getArgument('username')){
            $currentDirectory = getcwd();
            $uploadDirectory = "\\web\\uploads\\";
            $filepath = $currentDirectory . $uploadDirectory . $input->getArgument('filename');
            if(!empty($user = $this->userManager->getByLogin($input->getArgument('username'))) ){
                if (file_exists($filepath) ) {
                    $output->writeln('File exist <comment>'.$filepath.'</comment>');
                    $fileArr = explode('.',$input->getArgument('filename'));
                    $keys = array_keys($fileArr);
                    $fileArr[$keys[sizeof($keys) - 1]];
            
                    $fileExtension = strtolower($fileArr[$keys[sizeof($keys) - 1]]);

                    if (! in_array($fileExtension,$fileExtensionsAllowed)) {
                        $output->writeln('Only <comment>'.implode(',',$fileExtensionsAllowed).'</comment> acceptable here.');
                    }else {
                        if($this->readCsv($filepath, $user)) {
                            $output->writeln('File <comment>Migrated successfully</comment>');
                        }
                    }
                } else {
                    $output->writeln('File not exist <comment>'.$filepath.'</comment>');
                }
            } else {
                $output->writeln('User <comment>not exist</comment>');
            }

        }else {
            $output->writeln('provide proper email and file in this format <comment>php console.php migrate_sitemap user-email filename</comment>');
        }
        
    }

    public function readCsv($path, $user)
    {
        $i = 1;
        $file = fopen($path, 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            if( $i == 1 ) {
                $i++;
                continue;
            }
            if( !empty($line[0]) ) {
                $host = $line[0];
                $hostname = str_replace("https://","",$line[0]);
                $hostname = str_replace("http://","",$hostname);
                $hostname = explode('/', $hostname, 2);

                if($website = $this->sitemapManager->getByHostname($hostname[0])){
                    if( !empty($hostname[1]) && empty($this->sitemapManager->check($website, $hostname[1])) ){
                        $this->sitemapManager->createPage($website, $hostname[1]);
                    }
                }else{
                    if ( $webId = $this->sitemapManager->create($user, $host, $hostname[0]) ){
                        if(!empty($hostname[1])){
                            $website = $this->sitemapManager->getById($webId);
                            $this->sitemapManager->createPage($website, $host);
                        }
                    }
                }
                
            }
             
        }
        fclose($file);
        return true;
    }

}