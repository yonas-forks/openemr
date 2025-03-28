<?php

/**
 * Portal OneTime for API
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c)2023-2025 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General public License 3
 */

require_once(__DIR__ . "/../../../../globals.php");

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Events\Messaging\SendNotificationEvent;
use OpenEMR\Services\PatientPortalService;

if (!CsrfUtils::verifyCsrfToken($_REQUEST["csrf_token_form"], 'contact-form')) {
    CsrfUtils::csrfNotVerified();
}

if (isset($_REQUEST['sendOneTime'])) {
    try {
        doOnetimeDocumentRequest();
    } catch (Exception $e) {
        die($e->getMessage());
    }
}

if (isset($_REQUEST['sendInvoiceOneTime'])) {
    try {
        doOnetimeInvoiceRequest();
    } catch (Exception $e) {
        die($e->getMessage());
    }
}

/**
 * @throws Exception
 */
function doOnetimeInvoiceRequest(): void
{
    $service = new PatientPortalService();
    // auto allow if a portal user else must be an admin
    if (!$service::isPortalUser()) {
        // default is admin documents
        if (!$service::verifyAcl()) {
            throw new Exception(xlt("Error! Not authorised. You must be an authorised portal user or admin."));
        }
    }
    $ot_pid = $_REQUEST['pid'] ?? 0;
    if (!empty($ot_pid)) {
        $patient = $service->getPatientDetails($ot_pid);
    } else {
        throw new Exception(xlt("Error! Missing patient id."));
    }
    $message = "Dear " . $patient['fname'] . ' ' . $patient['lname'] . ",\n";
    $message .= xlt("Please review your current invoice by clinking the link to automatically redirect to your billing account portal. Use this PIN to complete authorization");
    $data = [
        'pid' => $ot_pid,
        'expiry_interval' => "P14D",
        'text_message' => $message,
        'html_message' => "",
        'redirect_url' => $GLOBALS['web_root'] . "/portal/home.php?site=" . urlencode($_SESSION['site_id']) . "&landOn=MakePayment",
        'phone' => $patient['phone'] ?? '',
        'email' => $patient['email'] ?? '',
        'actions' => [
            'enforce_onetime_use' => true,
            'enforce_auth_pin' => true,
            'extend_portal_visit' => false,
        ]
    ];
    try {
        $rtn = $GLOBALS["kernel"]->getEventDispatcher()
            ->dispatch(new SendNotificationEvent($data['pid'], $data, 'email'), SendNotificationEvent::SEND_NOTIFICATION_SERVICE_UNIVERSAL_ONETIME);
    } catch (Exception $e) {
        die($e->getMessage());
    }
}

/**
 * @throws Exception
 */
function doOnetimeDocumentRequest(): void
{
    $service = new PatientPortalService();
    // auto allow if a portal user else must be an admin
    if (!$service::isPortalUser()) {
        // default is admin documents
        if (!$service::verifyAcl()) {
            throw new Exception(xlt("Error! Not authorised. You must be an authorised portal user or admin."));
        }
    }
    $details = json_decode($service->getRequest('details'), true);
    $content = $service->getRequest('comments');
    $ot_pid = $details['pid'] ?? $service->getRequest('form_pid');
    if (!empty($ot_pid)) {
        $patient = $service->getPatientDetails($ot_pid);
    } else {
        throw new Exception(xlt("Error! Missing patient id."));
    }
    $data = [
        'pid' => $details['pid'] ?? 0,
        'onetime_period' => $details['onetime_period'] ?? 'PT60M',
        'notification_template_name' => $details['notification_template_name'] ?? '',
        'document_id' => $details['id'] ?? 0,
        'audit_id' => $details['audit_id'] ?? 0,
        'document_name' => $details['template_name'] ?? '',
        'notification_method' => $service->getRequest('notification_method', 'both'),
        'phone' => $patient['phone'] ?? '',
        'email' => $patient['email'] ?? '',
        'onetime' => $details['onetime'] ?? 0
    ];
    try {
        $rtn = $service->dispatchPortalOneTimeDocumentRequest($ot_pid, $data, $content);
    } catch (Exception $e) {
        die($e->getMessage());
    }
    echo js_escape($rtn);
}
