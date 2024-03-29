<?php

declare(strict_types=1);

namespace App\Story\Domain\Command;

use App\Common\Domain\Command\CommandHandlerInterface;
use App\Common\Domain\Event\EventBusInterface;
use App\Common\Domain\Model\EditableInterface;
use App\Common\Domain\Security\AuthorizationCheckerInterface;
use App\Common\Domain\Validator\ConstraintViolation;
use App\Common\Domain\Validator\ValidationFailedException;
use App\Common\Domain\Validator\ValidatorInterface;
use App\Language\Domain\Repository\LanguageRepositoryInterface;
use App\Story\Domain\Event\StoryUpdatedEvent;
use App\Story\Domain\Model\StoryTheme;
use App\Story\Domain\Model\StoryThemeDepthException;
use App\Story\Domain\Repository\StoryImageRepositoryInterface;
use App\Story\Domain\Repository\StoryRepositoryInterface;
use App\Story\Domain\Repository\StoryThemeRepositoryInterface;

final class StoryUpdateCommandHandler implements CommandHandlerInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private EventBusInterface $eventBus;
    private LanguageRepositoryInterface $languageRepository;
    private StoryRepositoryInterface $storyRepository;
    private StoryImageRepositoryInterface $storyImageRepository;
    private StoryThemeRepositoryInterface $storyThemeRepository;
    private ValidatorInterface $validator;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, EventBusInterface $eventBus, LanguageRepositoryInterface $languageRepository, StoryRepositoryInterface $storyRepository, StoryImageRepositoryInterface $storyImageRepository, StoryThemeRepositoryInterface $storyThemeRepository, ValidatorInterface $validator)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->eventBus = $eventBus;
        $this->languageRepository = $languageRepository;
        $this->storyRepository = $storyRepository;
        $this->storyImageRepository = $storyImageRepository;
        $this->storyThemeRepository = $storyThemeRepository;
        $this->validator = $validator;
    }

    public function __invoke(StoryUpdateCommand $command): array
    {
        try {
            $this->validator->validate($command);

            $story = $this->storyRepository->findOne($command->getId());

            $this->authorizationChecker->isGranted(EditableInterface::UPDATE, $story);

            $language = $this->languageRepository->findOne($command->getLanguageId());

            $story
                ->updateTitle($command->getTitle())
                ->updateContent($command->getContent())
                ->updateExtract($command->getExtract())
                ->updateLanguage($language)
            ;

            if (null !== $command->getStoryImageId()) {
                $storyImage = $this->storyImageRepository->findOne($command->getStoryImageId());
                $story->updateStoryImage($storyImage);
            } else {
                $story->updateStoryImage(null);
            }

            $story->updateStoryThemes($command->getStoryThemeIds(), $this->storyThemeRepository);

            $this->validator->validate($story);

            $this->storyRepository->flush();

            $event = (new StoryUpdatedEvent())
                ->setId($story->getId())
                ->setTitle($story->getTitle())
                ->setTitleSlug($story->getTitleSlug())
                ->setContent($story->getContent())
                ->setExtract($story->getExtract())
                ->setLanguageId($story->getLanguage()->getId())
                ->setStoryImageId(null !== $story->getStoryImage() ? $story->getStoryImage()->getId() : null)
                ->setStoryThemeIds(StoryTheme::extractIds($story->getStoryThemes()->toArray()))
            ;

            $this->validator->validate($event);

            $this->eventBus->dispatch($event);

            return [];
        } catch (StoryThemeDepthException $e) {
            throw new ValidationFailedException([
                new ConstraintViolation('story_theme_ids', $e->getMessage()),
            ]);
        }
    }
}
