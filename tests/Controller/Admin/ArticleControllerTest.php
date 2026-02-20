<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

    public function testIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/articles');

        self::assertResponseRedirects('/login');
    }

    public function testIndexRendersArticleList(): void
    {
        $this->client->loginUser($this->createAdmin());
        $this->createArticle('First Article', 'first-article');
        $this->createArticle('Second Article', 'second-article');

        $this->client->request('GET', '/admin/articles');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Articles');
        self::assertSelectorTextContains('table', 'First Article');
        self::assertSelectorTextContains('table', 'Second Article');
    }

    public function testIndexShowsEmptyState(): void
    {
        $this->client->loginUser($this->createAdmin());

        $this->client->request('GET', '/admin/articles');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main .bg-white', 'No articles yet');
    }

    public function testNewRendersForm(): void
    {
        $this->client->loginUser($this->createAdmin());

        $this->client->request('GET', '/admin/articles/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'New Article');
        self::assertSelectorExists('input[name="article[title]"]');
        self::assertSelectorExists('textarea[name="article[content]"]');
    }

    public function testNewCreatesArticle(): void
    {
        $this->client->loginUser($this->createAdmin());

        $this->client->request('GET', '/admin/articles/new');
        $this->client->submitForm('Create Article', [
            'article[title]' => 'My New Article',
            'article[content]' => 'This is the content of my new article.',
        ]);

        self::assertResponseRedirects('/admin/articles');

        $this->client->followRedirect();

        self::assertSelectorTextContains('.bg-green-50', 'Article created successfully.');
        self::assertSelectorTextContains('table', 'My New Article');
    }

    public function testEditRendersFormWithExistingData(): void
    {
        $this->client->loginUser($this->createAdmin());
        $article = $this->createArticle('Original Title', 'original-title', 'Original content.');

        $this->client->request('GET', '/admin/articles/'.$article->getId().'/edit');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Edit Article');
        self::assertInputValueSame('article[title]', 'Original Title');
    }

    public function testEditUpdatesArticle(): void
    {
        $this->client->loginUser($this->createAdmin());
        $article = $this->createArticle('Original Title', 'original-title', 'Original content.');

        $this->client->request('GET', '/admin/articles/'.$article->getId().'/edit');
        $this->client->submitForm('Update Article', [
            'article[title]' => 'Updated Title',
            'article[content]' => 'Updated content.',
        ]);

        self::assertResponseRedirects('/admin/articles');

        $this->client->followRedirect();

        self::assertSelectorTextContains('.bg-green-50', 'Article updated successfully.');
        self::assertSelectorTextContains('table', 'Updated Title');
    }

    public function testDeleteRemovesArticle(): void
    {
        $this->client->loginUser($this->createAdmin());
        $article = $this->createArticle('To Be Deleted', 'to-be-deleted');

        // Visit the index page to get the delete form with a valid CSRF token.
        $crawler = $this->client->request('GET', '/admin/articles');
        self::assertSelectorTextContains('table', 'To Be Deleted');

        // Submit the delete form rendered on the page.
        $deleteForm = $crawler->filter('form[action="/admin/articles/'.$article->getId().'"]')->form();
        $this->client->submit($deleteForm);

        self::assertResponseRedirects('/admin/articles');

        $this->client->followRedirect();

        self::assertSelectorTextContains('.bg-green-50', 'Article deleted successfully.');
        self::assertSelectorTextContains('main .bg-white', 'No articles yet');
    }

    public function testDeleteRequiresValidCsrfToken(): void
    {
        $this->client->loginUser($this->createAdmin());
        $article = $this->createArticle('Should Survive', 'should-survive');

        $this->client->request('DELETE', '/admin/articles/'.$article->getId(), [
            '_token' => 'invalid-token',
        ]);

        self::assertResponseRedirects('/admin/articles');

        $this->client->followRedirect();

        self::assertSelectorTextContains('table', 'Should Survive');
    }

    public function testNewRejectsEmptyTitle(): void
    {
        $this->client->loginUser($this->createAdmin());

        $this->client->request('GET', '/admin/articles/new');
        $this->client->submitForm('Create Article', [
            'article[title]' => '   ',
            'article[content]' => 'Some content.',
        ]);

        self::assertResponseIsUnprocessable();
        self::assertSelectorTextContains('h1', 'New Article');
    }

    public function testNewRejectsEmptyContent(): void
    {
        $this->client->loginUser($this->createAdmin());

        $this->client->request('GET', '/admin/articles/new');
        $this->client->submitForm('Create Article', [
            'article[title]' => 'A Valid Title',
            'article[content]' => '   ',
        ]);

        self::assertResponseIsUnprocessable();
        self::assertSelectorTextContains('h1', 'New Article');
    }

    public function testEditReturns404ForNonExistentArticle(): void
    {
        $this->client->loginUser($this->createAdmin());

        $this->client->request('GET', '/admin/articles/999/edit');

        self::assertResponseStatusCodeSame(404);
    }

    private function createAdmin(): User
    {
        $container = self::getContainer();

        $user = new User();
        $user->setEmail('admin@phpnews.com');
        $user->setPassword(
            $container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'secret123'),
        );

        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function createArticle(string $title, string $slug, string $content = 'Some content.'): Article
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
