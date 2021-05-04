<?php

declare(strict_types=1);

namespace App\Test\User\Presentation\Action;

use App\Common\Domain\Translation\TranslatorInterface;
use App\Fixture\Language\LanguageFixture;
use App\Fixture\User\AccessTokenFixture;
use App\Fixture\User\UserFixture;
use App\User\Domain\Model\UserGender;
use App\User\Domain\Model\UserStatus;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Uuid;

/**
 * @internal
 * @coversNothing
 */
final class UserGetActionTest extends AbstractUserActionTest
{
    protected function setUp(): void
    {
        parent::setUp();

        self::$httpMethod = Request::METHOD_GET;
        self::$httpUri = '/user/'.UserFixture::DATA['user-john']['id'];
        self::$httpAuthorization = 'Bearer '.AccessTokenFixture::DATA['access-token-john']['id'];
    }

    public function testSucceededSameUserLoggedIn(): void
    {
        $this->checkSucceeded([], [
            'editable' => true,
            'language_reference' => UserFixture::DATA['user-john']['language_reference'],
        ]);
    }

    public function testSucceededDifferentUserLoggedInButAdmin(): void
    {
        // change user logged in
        self::$httpAuthorization = 'Bearer '.AccessTokenFixture::DATA['access-token-yannis']['id'];

        $this->checkSucceeded([], [
            'editable' => true,
            'language_reference' => UserFixture::DATA['user-yannis']['language_reference'],
        ]);
    }

    public function testSucceededDifferentUserLoggedInButModerator(): void
    {
        // change user logged in
        self::$httpAuthorization = 'Bearer '.AccessTokenFixture::DATA['access-token-leslie']['id'];

        $this->checkSucceeded([], [
            'editable' => true,
            'language_reference' => UserFixture::DATA['user-leslie']['language_reference'],
        ]);
    }

    public function testSucceededDifferentUserLoggedIn(): void
    {
        // change user logged in
        self::$httpAuthorization = 'Bearer '.AccessTokenFixture::DATA['access-token-juliette']['id'];

        $this->checkSucceeded([], [
            'editable' => false,
            'language_reference' => UserFixture::DATA['user-juliette']['language_reference'],
        ]);
    }

    public function testSucceededNoUserLoggedInButEnglish(): void
    {
        // no user logged in
        self::$httpAuthorization = null;

        $this->checkSucceeded([], [
            'editable' => false,
            'language_reference' => 'language-english',
        ]);
    }

    public function testSucceededNoUserLoggedInButFrench(): void
    {
        // change locale
        self::$httpUri = '/user/'.UserFixture::DATA['user-john']['id'].'?_locale=fr';

        // no user logged in
        self::$httpAuthorization = null;

        $this->checkSucceeded([], [
            'editable' => false,
            'language_reference' => 'language-french',
        ]);
    }

    public function testFailedNotFoundWrongFormatId(): void
    {
        // wrong uri format
        self::$httpUri = '/user/id';

        $this->checkFailedNotFound();
    }

    public function testFailedNotFoundNonExistentId(): void
    {
        // non existent id
        self::$httpUri = '/user/'.Uuid::v4()->toRfc4122();

        $this->checkFailedNotFound();
    }

    public function testFailedNotFoundUserBlocked(): void
    {
        self::$httpUri = '/user/'.UserFixture::DATA['user-john']['id'];

        // block user
        $user = $this->userRepository->findOne(UserFixture::DATA['user-john']['id']);
        $user->setStatus(UserStatus::BLOCKED);
        $this->userRepository->flush();

        $this->checkFailedNotFound();
    }

    protected function checkProcessHasBeenSucceeded(array $responseData = [], array $options = []): void
    {
        $this->assertEquals(UserFixture::DATA['user-john']['id'], $responseData['user']['id']);
        $this->assertEquals(UserFixture::DATA['user-john']['gender'], $responseData['user']['gender']);
        $this->assertEquals(self::$container->get(TranslatorInterface::class)->trans(strtolower(UserGender::getTranslationPrefix().UserFixture::DATA['user-john']['gender']), [], null, LanguageFixture::DATA[$options['language_reference']]['locale']), $responseData['user']['gender_reading']);
        $this->assertEquals(UserFixture::DATA['user-john']['name'], $responseData['user']['name']);
        $this->assertEquals((new AsciiSlugger())->slug(UserFixture::DATA['user-john']['name'])->lower()->toString(), $responseData['user']['name_slug']);
        $this->assertFalse($responseData['user']['image_defined']);
        $this->assertEquals(LanguageFixture::DATA[UserFixture::DATA['user-john']['language_reference']]['id'], $responseData['user']['language']['id']);
        $this->assertEquals(LanguageFixture::DATA[UserFixture::DATA['user-john']['language_reference']]['title'], $responseData['user']['language']['title']);
        $this->assertEquals(LanguageFixture::DATA[UserFixture::DATA['user-john']['language_reference']]['locale'], $responseData['user']['language']['locale']);
        $this->assertTrue(new DateTime() > new DateTime($responseData['user']['created_at']));
        $this->assertEquals($options['editable'], $responseData['user']['editable']);
        $this->assertCount(1, $responseData['user']['reading_languages']);
        $this->assertEquals(LanguageFixture::DATA[UserFixture::DATA['user-john']['reading_language_references'][0]]['id'], $responseData['user']['reading_languages'][0]['id']);
        $this->assertEquals(LanguageFixture::DATA[UserFixture::DATA['user-john']['reading_language_references'][0]]['title'], $responseData['user']['reading_languages'][0]['title']);
        $this->assertEquals(LanguageFixture::DATA[UserFixture::DATA['user-john']['reading_language_references'][0]]['locale'], $responseData['user']['reading_languages'][0]['locale']);
    }

    protected function checkProcessHasBeenStopped(): void
    {
        // nothing to check
    }
}
