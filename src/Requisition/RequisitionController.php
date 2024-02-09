<?php

declare(strict_types=1);

namespace App\Requisition;

use App\Requisition\Create\RequisitionForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RequisitionController extends AbstractController
{
    private const REQUISITION_CREATED_MESSAGE = 'Requisition created successfully, thank you!';

    #[Route('/', 'create_requisition')]
    public function createRequisition(Request $request): Response
    {
        $createRequisitionForm = $this->createForm(RequisitionForm::class);
        $createRequisitionForm->handleRequest($request);
        if ($createRequisitionForm->isSubmitted() && $createRequisitionForm->isValid()) {
            try {
                $this->addFlash('success', self::REQUISITION_CREATED_MESSAGE);
                $createRequisitionForm = $this->createForm(RequisitionForm::class); // clear form
            } catch (\Exception $exception) {
                $createRequisitionForm->get('title')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('@app.src_dir/Requisition/Create/output.twig', [
            'form' => $createRequisitionForm,
        ]);
    }
}
