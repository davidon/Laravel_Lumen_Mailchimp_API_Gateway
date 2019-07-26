<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20181103113102 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mail_chimp_members (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', list_id VARCHAR(255) NOT NULL, mail_chimp_id VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, vip TINYINT(1) NOT NULL, location LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', ip_signup VARCHAR(255) NOT NULL, tags LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', email_id VARCHAR(255) NOT NULL, unique_email_id VARCHAR(255) NOT NULL, member_rating INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE mail_chimp_members');
    }
}
