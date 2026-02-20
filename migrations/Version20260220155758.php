<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220155758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user and messenger_messages tables';
    }

    public function up(Schema $schema): void
    {
        $user = $schema->createTable('user');
        $user->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $user->addColumn('email', Types::STRING, ['length' => 180]);
        $user->addColumn('password', Types::STRING, ['length' => 255]);
        $user->setPrimaryKey(['id']);
        $user->addUniqueIndex(['email'], 'UNIQ_IDENTIFIER_EMAIL');

        $messenger = $schema->createTable('messenger_messages');
        $messenger->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $messenger->addColumn('body', Types::TEXT);
        $messenger->addColumn('headers', Types::TEXT);
        $messenger->addColumn('queue_name', Types::STRING, ['length' => 190]);
        $messenger->addColumn('created_at', Types::DATETIME_MUTABLE);
        $messenger->addColumn('available_at', Types::DATETIME_MUTABLE);
        $messenger->addColumn('delivered_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $messenger->setPrimaryKey(['id']);
        $messenger->addIndex(['queue_name', 'available_at', 'delivered_at', 'id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('user');
        $schema->dropTable('messenger_messages');
    }
}
