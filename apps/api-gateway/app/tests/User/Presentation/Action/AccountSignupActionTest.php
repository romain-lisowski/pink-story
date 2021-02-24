<?php

declare(strict_types=1);

namespace App\Test\User\Presentation\Action;

use App\Common\Infrastructure\Serializer\Normalizer\DataUriNormalizer;
use App\User\Domain\Event\UserCreatedEvent;
use App\User\Domain\Model\UserGender;
use App\User\Domain\Model\UserRole;
use App\User\Domain\Model\UserStatus;
use App\User\Domain\Security\UserPasswordEncoderInterface;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Uid\Uuid;

/**
 * @internal
 * @coversNothing
 */
final class AccountSignupActionTest extends AbastractUserActionTest
{
    private const USER_DATA = [
        'gender' => UserGender::UNDEFINED,
        'name' => 'Test',
        'email' => 'test@pinkstory.io',
        'password' => '@Password2!',
    ];

    public function testSuccessWithoutImage(): void
    {
        $this->client->request('POST', '/account/signup', [], [], [], json_encode([
            'gender' => self::USER_DATA['gender'],
            'name' => self::USER_DATA['name'],
            'email' => self::USER_DATA['email'],
            'password' => self::USER_DATA['password'],
        ]));

        // check http response
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals([], $responseContent);

        $this->assertTrue($this->hasDataBeenSavedInDatabase());
        $this->hasDataBeenFullySavedInDatabase(false);

        // check event has been dispatched
        $this->assertCount(1, $this->asyncTransport->get());
        $this->assertInstanceOf(UserCreatedEvent::class, $this->asyncTransport->get()[0]->getMessage());
        $this->hasEventBeenFullyDispatched($this->asyncTransport->get()[0]->getMessage(), false);
    }

    public function testSuccessWithImage(): void
    {
        $this->client->request('POST', '/account/signup', [], [], [], json_encode([
            'gender' => self::USER_DATA['gender'],
            'name' => self::USER_DATA['name'],
            'email' => self::USER_DATA['email'],
            'password' => self::USER_DATA['password'],
            'image' => (new DataUriNormalizer())->normalize(new File(__DIR__.'/../../../image/test.jpg'), ''),
        ]));

        // check http response
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals([], $responseContent);

        $this->assertTrue($this->hasDataBeenSavedInDatabase());
        $this->hasDataBeenFullySavedInDatabase(true);

        // check image has been uploaded
        $user = $this->userRepository->findOneByEmail(self::USER_DATA['email']);
        $this->assertTrue((new Filesystem())->exists(self::$container->getParameter('project_image_storage_path').$user->getImagePath()));

        // check event has been dispatched
        $this->assertCount(1, $this->asyncTransport->get());
        $this->assertInstanceOf(UserCreatedEvent::class, $this->asyncTransport->get()[0]->getMessage());
        $this->hasEventBeenFullyDispatched($this->asyncTransport->get()[0]->getMessage(), true);
    }

    public function testFailedNonExistentGender(): void
    {
        $this->client->request('POST', '/account/signup', [], [], [], json_encode([
            'gender' => 'gender',
            'name' => self::USER_DATA['name'],
            'email' => self::USER_DATA['email'],
            'password' => self::USER_DATA['password'],
        ]));

        // check http response
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('validation_failed_exception', $responseContent['exception']['type']);
        $this->assertEquals('gender', $responseContent['exception']['violations'][0]['property_path']);

        $this->assertFalse($this->hasDataBeenSavedInDatabase());

        // check event has not been dispatched
        $this->assertCount(0, $this->asyncTransport->get());
    }

    public function testFailedWrongFormatEmail(): void
    {
        $this->client->request('POST', '/account/signup', [], [], [], json_encode([
            'gender' => self::USER_DATA['gender'],
            'name' => self::USER_DATA['name'],
            'email' => 'email',
            'password' => self::USER_DATA['password'],
        ]));

        // check http response
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('validation_failed_exception', $responseContent['exception']['type']);
        $this->assertEquals('email', $responseContent['exception']['violations'][0]['property_path']);

        $this->assertFalse($this->hasDataBeenSavedInDatabase());

        // check event has not been dispatched
        $this->assertCount(0, $this->asyncTransport->get());
    }

    public function testFailedNonExistentEmail(): void
    {
        $this->client->request('POST', '/account/signup', [], [], [], json_encode([
            'gender' => self::USER_DATA['gender'],
            'name' => self::USER_DATA['name'],
            'email' => 'email@email.em',
            'password' => self::USER_DATA['password'],
        ]));

        // check http response
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('validation_failed_exception', $responseContent['exception']['type']);
        $this->assertEquals('email', $responseContent['exception']['violations'][0]['property_path']);

        $this->assertFalse($this->hasDataBeenSavedInDatabase());

        // check event has not been dispatched
        $this->assertCount(0, $this->asyncTransport->get());
    }

    public function testFailedNonUniqueEmail(): void
    {
        $this->client->request('POST', '/account/signup', [], [], [], json_encode([
            'gender' => self::USER_DATA['gender'],
            'name' => self::USER_DATA['name'],
            'email' => 'hello@pinkstory.io',
            'password' => self::USER_DATA['password'],
        ]));

        // check http response
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('validation_failed_exception', $responseContent['exception']['type']);
        $this->assertEquals('email', $responseContent['exception']['violations'][0]['property_path']);

        $this->assertFalse($this->hasDataBeenSavedInDatabase());

        // check event has not been dispatched
        $this->assertCount(0, $this->asyncTransport->get());
    }

    public function testFailedPasswordStrenght(): void
    {
        $this->client->request('POST', '/account/signup', [], [], [], json_encode([
            'gender' => self::USER_DATA['gender'],
            'name' => self::USER_DATA['name'],
            'email' => self::USER_DATA['email'],
            'password' => 'password',
        ]));

        // check http response
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('validation_failed_exception', $responseContent['exception']['type']);
        $this->assertEquals('password', $responseContent['exception']['violations'][0]['property_path']);

        $this->assertFalse($this->hasDataBeenSavedInDatabase());

        // check event has not been dispatched
        $this->assertCount(0, $this->asyncTransport->get());
    }

    private function hasDataBeenSavedInDatabase(): bool
    {
        try {
            $this->userRepository->findOneByEmail(self::USER_DATA['email']);

            return true;
        } catch (NoResultException $e) {
            return false;
        }
    }

    private function hasDataBeenFullySavedInDatabase(bool $shouldHaveImageDefined = false): void
    {
        $user = $this->userRepository->findOneByEmail(self::USER_DATA['email']);

        $this->assertTrue(Uuid::isValid($user->getId()));
        $this->assertEquals(self::USER_DATA['gender'], $user->getGender());
        $this->assertEquals(self::USER_DATA['name'], $user->getName());
        $this->assertEquals(self::USER_DATA['email'], $user->getEmail());
        $this->assertFalse($user->isEmailValidated());
        $this->assertRegExp('/([0-9]{6})/', $user->getEmailValidationCode());
        $this->assertFalse($user->isEmailValidationCodeUsed());
        $this->assertTrue(self::$container->get(UserPasswordEncoderInterface::class)->isPasswordValid($user, self::USER_DATA['password']));
        $this->assertEquals($shouldHaveImageDefined, $user->isImageDefined());
        $this->assertEquals(UserRole::USER, $user->getRole());
        $this->assertEquals(UserStatus::ACTIVATED, $user->getStatus());
    }

    private function hasEventBeenFullyDispatched(UserCreatedEvent $event, bool $shouldHaveImageDefined = false): void
    {
        $this->assertTrue(Uuid::isValid($event->getId()));
        $this->assertEquals(self::USER_DATA['gender'], $event->getGender());
        $this->assertEquals(self::USER_DATA['name'], $event->getName());
        $this->assertEquals(self::USER_DATA['email'], $event->getEmail());
        $this->assertRegExp('/([0-9]{6})/', $event->getEmailValidationCode());
        $this->assertNotNull($event->getPassword());

        if (true === $shouldHaveImageDefined) {
            $this->assertNotNull($event->getImagePath());
        } else {
            $this->assertNull($event->getImagePath());
        }

        $this->assertEquals(UserRole::USER, $event->getRole());
        $this->assertEquals(UserStatus::ACTIVATED, $event->getStatus());
    }
}
