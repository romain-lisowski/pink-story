<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

final class FormManager implements FormManagerInterface
{
    private FormFactoryInterface $formFactory;
    private FormInterface $form;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function initForm(FormableInterface $formable, array $options = []): self
    {
        $this->form = $this->formFactory->create($formable->getFormType(), $formable, $options);

        return $this;
    }

    public function handleRequest(Request $request): void
    {
        $this->form->handleRequest($request);

        if (true === $this->form->isSubmitted() && false === $this->form->isValid()) {
            throw new InvalidFormException($this->form->getErrors(true));
        }
    }
}
