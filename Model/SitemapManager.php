<?php

namespace Ahmad\SitemapMigrate\Model;

use Snowdog\DevTest\Core\Database;

class SitemapManager
{
    /**
     * @var Database|\PDO
     */
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function getByLogin($login)
    {
        /** @var \PDOStatement $query */
        $query = $this->database->prepare('SELECT * FROM users WHERE login = :login');
        $query->setFetchMode(\PDO::FETCH_CLASS, User::class);
        $query->bindParam(':login', $login, \PDO::PARAM_STR);
        $query->execute();
        /** @var User $user */
        $user = $query->fetch(\PDO::FETCH_CLASS);
        return $user;
    }

    public function getByHostname($hostname) {
        /** @var \PDOStatement $query */
        $query = $this->database->prepare('SELECT * FROM websites WHERE hostname = :hostname');
        $query->setFetchMode(\PDO::FETCH_CLASS, Website::class);
        $query->bindParam(':hostname', $hostname, \PDO::PARAM_STR);
        $query->execute();
        /** @var Website $website */
        $website = $query->fetch(\PDO::FETCH_CLASS);
        return $website;
    }
    
    public function check(Website $website, $url)
    {
        $websiteId = $website->getWebsiteId();
        /** @var \PDOStatement $query */
        $query = $this->database->prepare('SELECT * FROM pages WHERE website_id = :website AND url = :url');
        $query->setFetchMode(\PDO::FETCH_CLASS, Page::class);
        $query->bindParam(':url', $url, \PDO::PARAM_STR);
        $query->bindParam(':website', $websiteId, \PDO::PARAM_INT);
        $query->execute();
        /** @var Website $website */
        return $query->fetch(\PDO::FETCH_CLASS);
    }

    public function create(User $user, $name, $hostname)
    {
        $userId = $user->getUserId();
        /** @var \PDOStatement $statement */
        $statement = $this->database->prepare('INSERT INTO websites (name, hostname, user_id) VALUES (:name, :host, :user)');
        $statement->bindParam(':name', $name, \PDO::PARAM_STR);
        $statement->bindParam(':host', $hostname, \PDO::PARAM_STR);
        $statement->bindParam(':user', $userId, \PDO::PARAM_INT);
        $statement->execute();
        return $this->database->lastInsertId();
    }

    public function createPage(Website $website, $url)
    {
        $websiteId = $website->getWebsiteId();
        /** @var \PDOStatement $statement */
        $statement = $this->database->prepare('INSERT INTO pages (url, website_id) VALUES (:url, :website)');
        $statement->bindParam(':url', $url, \PDO::PARAM_STR);
        $statement->bindParam(':website', $websiteId, \PDO::PARAM_INT);
        $statement->execute();
        return $this->database->lastInsertId();
    }
}
