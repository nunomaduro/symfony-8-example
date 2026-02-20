<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArticleControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testShowRendersArticle(): void
    {
        $this->createArticle('Hello World', 'hello-world', 'This is my first article.');

        $this->client->request('GET', '/article/hello-world');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Hello World');
        self::assertSelectorTextContains('article', 'This is my first article.');
    }

    public function testShowReturns404ForNonExistentSlug(): void
    {
        $this->client->request('GET', '/article/does-not-exist');

        self::assertResponseStatusCodeSame(404);
    }

    public function testShowDisplaysPublishedDate(): void
    {
        $this->createArticle('Dated Article', 'dated-article', 'Some content.');

        $this->client->request('GET', '/article/dated-article');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('time[datetime]');
    }

    public function testShowHasBackLink(): void
    {
        $this->createArticle('Some Article', 'some-article', 'Content here.');

        $this->client->request('GET', '/article/some-article');

        self::assertSelectorExists('a[href="/"]');
    }

    private function createArticle(string $title, string $slug, string $content): Article
    {
        $article = new Article();
        $article->setTitle($title);
        $article->setSlug($slug);
        $article->setContent($content);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($article);
        $entityManager->flush();

        return $article;
    }
}
