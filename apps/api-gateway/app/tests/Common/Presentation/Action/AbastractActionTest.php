<?php

declare(strict_types=1);

namespace App\Test\Common\Presentation\Action;

use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @internal
 * @coversNothing
 */
abstract class AbastractActionTest extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    protected TransportInterface $asyncTransport;
    protected UserRepositoryInterface $userRepository;

    protected static string $userId = 'dc8d7267-fcb8-4f42-b164-a08e7cb9296b';
    protected static User $user;

    protected static string $httpMethod = Request::METHOD_GET;
    protected static string $httpUri = '';
    protected static ?string $httpAuthorization = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');

        $this->asyncTransport = self::$container->get('messenger.transport.async');

        $this->userRepository = self::$container->get('doctrine')->getManager()->getRepository(User::class);
        self::$user = $this->userRepository->findOne(self::$userId);

        self::$httpAuthorization = 'Bearer '.self::$user->getAccessTokens()->first()->getId();
    }

    protected function tearDown(): void
    {
        // remove test images
        (new Filesystem())->remove(self::$container->getParameter('project_image_storage_path'));

        parent::tearDown();
    }

    protected function checkSuccess(?array $requestContent = [], array $expectedResponseData = [], array $processOptions = []): void
    {
        $this->client->request(static::$httpMethod, static::$httpUri, [], [], [
            'HTTP_AUTHORIZATION' => null !== static::$httpAuthorization ? static::$httpAuthorization : '',
        ], json_encode($requestContent));

        // check http response
        $this->checkHttpResponseSuccess($expectedResponseData);

        // check process has been succeeded
        $this->checkProcessHasBeenSucceeded($processOptions);
    }

    protected function checkHttpResponseSuccess(array $expectedResponseData = []): void
    {
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($expectedResponseData, $responseContent);
    }

    protected function checkFailedUnauthorized(?array $requestContent = []): void
    {
        $this->client->request(static::$httpMethod, static::$httpUri, [], [], [], json_encode($requestContent));

        // check http response
        $this->checkHttpResponseUnauthorized();

        // check process has been stopped
        $this->checkProcessHasBeenStopped();
    }

    protected function checkHttpResponseUnauthorized(): void
    {
        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('insufficient_authentication_exception', $responseContent['exception']['type']);
    }

    protected function checkFailedMissingMandatory(?array $requestContent = []): void
    {
        $this->client->request(static::$httpMethod, static::$httpUri, [], [], [
            'HTTP_AUTHORIZATION' => null !== static::$httpAuthorization ? static::$httpAuthorization : '',
        ], json_encode($requestContent));

        // check http response
        $this->checkHttpResponseMissingMandatory();

        // check process has been stopped
        $this->checkProcessHasBeenStopped();
    }

    protected function checkHttpResponseMissingMandatory(): void
    {
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('request_body_param_missing_mandatory_exception', $responseContent['exception']['type']);
    }

    protected function checkFailedValidationFailed(?array $requestContent = [], array $invalidFields = []): void
    {
        $this->client->request(static::$httpMethod, static::$httpUri, [], [], [
            'HTTP_AUTHORIZATION' => null !== static::$httpAuthorization ? static::$httpAuthorization : '',
        ], json_encode($requestContent));

        // check http response
        $this->checkHttpResponseValidationFailed($invalidFields);

        // check process has been stopped
        $this->checkProcessHasBeenStopped();
    }

    protected function checkHttpResponseValidationFailed(array $invalidFields = []): void
    {
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('validation_failed_exception', $responseContent['exception']['type']);

        foreach ($responseContent['exception']['violations'] as $violation) {
            $this->assertTrue(in_array($violation['property_path'], $invalidFields));
        }
    }

    abstract protected function checkProcessHasBeenSucceeded(array $options = []): void;

    abstract protected function checkProcessHasBeenStopped(): void;
}
