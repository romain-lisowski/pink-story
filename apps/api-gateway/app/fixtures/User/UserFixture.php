<?php

namespace App\Fixture\User;

use App\User\Entity\User;
use App\User\Entity\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class UserFixture extends Fixture
{
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User('PinkStory', 'hello@pinkstory.io', UserRole::ROLE_GOD, $this->getReference('language-french'));
        $user->updatePassword($this->passwordEncoder->encodePassword($user, '@Password2!'));
        $manager->persist($user);
        $this->addReference('user-pinkstory', $user);

        $user = new User('Yannis', 'hello@yannissgarra.com', UserRole::ROLE_GOD, $this->getReference('language-french'));
        $user->updatePassword($this->passwordEncoder->encodePassword($user, '@Password2!'));
        $manager->persist($user);
        $this->addReference('user-yannis', $user);

        $user = new User('Romain', 'romain.lisowski@gmail.com', UserRole::ROLE_GOD, $this->getReference('language-french'));
        $user->updatePassword($this->passwordEncoder->encodePassword($user, '@Password2!'));
        $manager->persist($user);
        $this->addReference('user-romain', $user);

        $user = new User('Leslie', 'leslie.akindou@gmail.com', UserRole::ROLE_MODERATOR, $this->getReference('language-french'));
        $user->updatePassword($this->passwordEncoder->encodePassword($user, '@Password2!'));
        $manager->persist($user);
        $this->addReference('user-leslie', $user);

        $user = new User('Juliette', 'contact@julietteverdurand.com', UserRole::ROLE_EDITOR, $this->getReference('language-french'));
        $user->updatePassword($this->passwordEncoder->encodePassword($user, '@Password2!'));
        $manager->persist($user);
        $this->addReference('user-juliette', $user);

        $manager->flush();
    }
}
