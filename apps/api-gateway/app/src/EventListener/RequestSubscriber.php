<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Language\Repository\LanguageRepositoryInterface;
use Doctrine\ORM\UnexpectedResultException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestSubscriber implements EventSubscriberInterface
{
    private LanguageRepositoryInterface $languageRepository;

    public function __construct(LanguageRepositoryInterface $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $language = $this->languageRepository->findOneByLocale('en');

        try {
            $language = $this->languageRepository->findOneByLocale($request->get('_locale', 'en'));
        } catch (UnexpectedResultException $e) {
            // do nothing
        }

        $request->attributes->set('language', $language);
        $request->setLocale($language->getLocale());
    }
}