<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220164135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create article table';
    }

    public function up(Schema $schema): void
    {
        $article = $schema->createTable('article');
        $article->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $article->addColumn('title', Types::STRING, ['length' => 255]);
        $article->addColumn('slug', Types::STRING, ['length' => 255]);
        $article->addColumn('content', Types::TEXT);
        $article->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $article->addColumn('updated_at', Types::DATETIME_IMMUTABLE);
        $article->setPrimaryKey(['id']);
        $article->addUniqueIndex(['slug']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('article');
    }
}
