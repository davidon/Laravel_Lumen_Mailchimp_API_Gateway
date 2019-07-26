<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20181103124352 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mail_chimp_members CHANGE language language VARCHAR(255) DEFAULT NULL, CHANGE vip vip TINYINT(1) DEFAULT NULL, CHANGE location location LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE ip_signup ip_signup VARCHAR(255) DEFAULT NULL, CHANGE tags tags LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE email_id email_id VARCHAR(255) DEFAULT NULL, CHANGE unique_email_id unique_email_id VARCHAR(255) DEFAULT NULL, CHANGE member_rating member_rating INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mail_chimp_members CHANGE language language VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE vip vip TINYINT(1) NOT NULL, CHANGE location location LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:array)\', CHANGE ip_signup ip_signup VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE tags tags LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:array)\', CHANGE email_id email_id VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE unique_email_id unique_email_id VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE member_rating member_rating INT NOT NULL');
    }
}
