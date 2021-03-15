<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class InvoiceTemplatesSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.invoice_templates' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event): void
    {
        if ($this->isGranted('view_invoice')) {
            $event->addBack($this->path('invoice'));
        }

        $event->addColumnToggle('#modal_invoice_template');

        if ($this->isGranted('manage_invoice_template')) {
            $event->addAction('create', ['url' => $this->path('admin_invoice_template_create'), 'class' => 'modal-ajax-form']);
        }

        // File upload does not work in a modal right now
        if ($this->isGranted('upload_invoice_template')) {
            $event->addAction('upload', ['url' => $this->path('admin_invoice_document_upload')]);
        }

        $event->addHelp($this->documentationLink('invoices.html'));
    }
}
