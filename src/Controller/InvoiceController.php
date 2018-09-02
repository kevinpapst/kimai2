<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\InvoiceTemplate;
use App\Entity\Timesheet;
use App\Form\InvoiceTemplateForm;
use App\Form\Toolbar\InvoiceToolbarForm;
use App\Invoice\ServiceInvoice;
use App\Model\InvoiceModel;
use App\Repository\InvoiceTemplateRepository;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\InvoiceQuery;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage invoices.
 *
 * @Route(path="/invoice")
 * @Security("is_granted('ROLE_TEAMLEAD')")
 */
class InvoiceController extends AbstractController
{
    /**
     * @var ServiceInvoice
     */
    protected $service;
    /**
     * @var InvoiceTemplateRepository
     */
    protected $invoiceRepository;
    /**
     * @var TimesheetRepository
     */
    protected $timesheetRepository;

    /**
     * @param ServiceInvoice $service
     * @param InvoiceTemplateRepository $invoice
     * @param TimesheetRepository $timesheet
     */
    public function __construct(ServiceInvoice $service, InvoiceTemplateRepository $invoice, TimesheetRepository $timesheet)
    {
        $this->service = $service;
        $this->invoiceRepository = $invoice;
        $this->timesheetRepository = $timesheet;
    }

    /**
     * @return InvoiceQuery
     * @throws \Exception
     */
    protected function getDefaultQuery()
    {
        $begin = new \DateTime('first day of this month');
        $end = new \DateTime('last day of this month');

        $query = new InvoiceQuery();
        $query->setBegin($begin);
        $query->setEnd($end);
        $query->setUser($this->getUser());
        $query->setState(InvoiceQuery::STATE_STOPPED);

        return $query;
    }

    /**
     * @Route(path="/", name="invoice", methods={"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function indexAction(Request $request)
    {
        if (!$this->invoiceRepository->hasTemplate()) {
            return $this->redirectToRoute('admin_invoice_template_create');
        }

        $entries = [];

        $query = $this->getDefaultQuery();
        $form = $this->getToolbarForm($query);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var InvoiceQuery $query */
            $query = $form->getData();
            $query->setResultType(TimesheetQuery::RESULT_TYPE_QUERYBUILDER);

            if (null !== $query->getCustomer()) {
                $query->getBegin()->setTime(0, 0, 0);
                $query->getEnd()->setTime(23, 59, 59);

                $queryBuilder = $this->timesheetRepository->findByQuery($query);
                $entries = $queryBuilder->getQuery()->getResult();
            }
        }

        $model = new InvoiceModel();
        $model->setQuery($query);
        $model->setEntries($entries);
        $model->setCustomer($query->getCustomer());

        $action = null;
        if ($query->getTemplate() !== null) {
            $generator = $this->service->getNumberGeneratorByName($query->getTemplate()->getNumberGenerator());
            if (null === $generator) {
                throw new \Exception('Unknown number generator: ' . $query->getTemplate()->getNumberGenerator());
            }

            $calculator = $this->service->getCalculatorByName($query->getTemplate()->getCalculator());
            if (null === $calculator) {
                throw new \Exception('Unknown invoice calculator: ' . $query->getTemplate()->getCalculator());
            }

            $model->setTemplate($query->getTemplate());
            $model->setCalculator($calculator);
            $model->setNumberGenerator($generator);
            $action = $this->service->getRendererActionByName($query->getTemplate()->getRenderer());
        }

        return $this->render('invoice/index.html.twig', [
            'model' => $model,
            'action' => $action,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/template", defaults={"page": 1}, name="admin_invoice_template", methods={"GET", "POST"})
     * @Route(path="/template/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_invoice_template_paginated", methods={"GET", "POST"})
     *
     * TODO permission
     *
     * @param $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listTemplateAction($page)
    {
        $templates = $this->invoiceRepository->findByQuery(new BaseQuery());

        return $this->render('invoice/templates.html.twig', [
            'entries' => $templates,
            'page' => $page,
        ]);
    }

    /**
     * @Route(path="/template/{id}/edit", name="admin_invoice_template_edit", methods={"GET", "POST"})
     *
     * TODO permission
     *
     * @param InvoiceTemplate $template
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function editTemplateAction(InvoiceTemplate $template, Request $request)
    {
        return $this->renderTemplateForm($template, $request);
    }

    /**
     * @Route(path="/template/create", name="admin_invoice_template_create", methods={"GET", "POST"})
     *
     * TODO permission
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function createTemplateAction(Request $request)
    {
        if (!$this->invoiceRepository->hasTemplate()) {
            $this->flashWarning('invoice.first_template');
        }

        return $this->renderTemplateForm(new InvoiceTemplate(), $request);
    }

    /**
     * The route to delete an existing template.
     *
     * TODO permission
     *
     * @Route(path="/template/{id}/delete", name="admin_invoice_template_delete", methods={"GET", "POST"})
     *
     * @param InvoiceTemplate $template
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteTemplate(InvoiceTemplate $template, Request $request)
    {
        try {
            $this->invoiceRepository->removeTemplate($template);
            $this->flashSuccess('action.delete.success');
        } catch (\Exception $ex) {
            $this->flashError('action.delete.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('admin_invoice_template_paginated', ['page' => $request->get('page')]);
    }

    /**
     * @param InvoiceTemplate $template
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function renderTemplateForm(InvoiceTemplate $template, Request $request)
    {
        $editForm = $this->createEditForm($template);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->invoiceRepository->saveTemplate($template);
                $this->flashSuccess('action.update.success');
                return $this->redirectToRoute('admin_invoice_template');
            } catch (\Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('invoice/template_edit.html.twig', [
            'template' => $template,
            'form' => $editForm->createView()
        ]);
    }

    /**
     * @param InvoiceModel $model
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function invoiceAction(InvoiceModel $model)
    {
        return $this->render('invoice/renderer/print.html.twig', [
            'model' => $model,
        ]);
    }

    /**
     * @param InvoiceModel $model
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function timesheetAction(InvoiceModel $model)
    {
        return $this->render('invoice/renderer/timesheet.html.twig', [
            'model' => $model,
        ]);
    }

    /**
     * @param InvoiceQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(InvoiceQuery $query)
    {
        return $this->createForm(InvoiceToolbarForm::class, $query, [
            'action' => $this->generateUrl('invoice', []),
            'method' => 'GET',
        ]);
    }

    /**
     * @param InvoiceTemplate $template
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createEditForm(InvoiceTemplate $template)
    {
        if ($template->getId() === null) {
            $url = $this->generateUrl('admin_invoice_template_create');
        } else {
            $url = $this->generateUrl('admin_invoice_template_edit', ['id' => $template->getId()]);
        }

        return $this->createForm(InvoiceTemplateForm::class, $template, [
            'action' => $url,
            'method' => 'POST'
        ]);
    }
}
