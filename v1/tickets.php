<?php
/**
 * osTicket API - Tickets Endpoint (v1)
 */

require_once('../../main.inc.php');
require_once(INCLUDE_DIR . 'class.api.php');
require_once(INCLUDE_DIR . 'class.ticket.php');

header('Content-Type: application/json');

// Validate API Key
$headers = function_exists('getallheaders') ? getallheaders() : [];
$headers = array_change_key_case($headers, CASE_LOWER);
$key = $headers['x-api-key'] ?? null;

if (!$key) {
    http_response_code(401);
    exit(json_encode(['error' => 'Missing API key']));
}

$api = Api::lookupByKey($key);
if (!$api) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid API key']));
}

if ($api->getIPAddr() && $api->getIPAddr() !== $_SERVER['REMOTE_ADDR']) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized IP']));
}

// Setup
$method = $_SERVER['REQUEST_METHOD'];
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $per_page;

// Methods
try {
    switch ($method) {
        // List or get tickets
        case 'GET':
            $tickets = Ticket::objects();

            // Filters
            if (!empty($_GET['id'])) $tickets->filter(['ticket_id' => (int)$_GET['id']]);
            if (!empty($_GET['status_id'])) $tickets->filter(['status_id' => (int)$_GET['status_id']]);
            if (!empty($_GET['dept_id'])) $tickets->filter(['dept_id' => (int)$_GET['dept_id']]);
            if (!empty($_GET['q'])) $tickets->filter(['cdata__subject__contains' => $_GET['q']]);
            if (!empty($_GET['email'])) $tickets->filter(['user__emails__address' => $_GET['email']]);
            if (!empty($_GET['topic_id'])) $tickets->filter(['topic_id' => (int)$_GET['topic_id']]);

            $total = $tickets->count();
            $tickets = $tickets->limit($per_page)->offset($offset);

            $data = [];
            foreach ($tickets as $t) {
                $thread = $t->getThread(); 
                $responseData = [];
                if ($thread) {
                    foreach ($thread->getResponses() as $msg) {
                        $responseData[] = [
                            'id'        => $msg->getId(),
                            'created'   => $msg->getCreateDate(),
                            'body'      => $msg->getBody(),
                            'user_id'   => $msg->getUser() ? $msg->getUser()->getId() : null,
                            'user_name' => $msg->getUser() ? $msg->getUser()->getName() : null,
                            'user_email'=> $msg->getUser() ? $msg->getUser()->getEmail() : null                       
                        ];
                    }
                }

                $data[] = [
                    'id' => $t->getId(),
                    'number' => $t->getNumber(),
                    'subject' => $t->getSubject(),
                    'status' => $t->getStatus()->getName(),
                    'status_id' => $t->getStatusId(),
                    'dept_id' => $t->getDeptId(),
                    'priority_id' => $t->getPriorityId(),
                    'topic_id' => $t->getTopicId(),
                    'created' => $t->getCreateDate(),
                    'updated' => $t->getUpdateDate(),
                    'user_email' => $t->getUser() ? $t->getUser()->getEmail() : null,
                    'user_name' => $t->getUser() ? $t->getUser()->getName() : null,
                    'responses' => $responseData,
                ];
            }

            echo json_encode([
                'tickets' => $data,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => $total,
                    'pages' => ceil($total / $per_page),
                ],
            ]);
            break;

        // Create a new ticket
        case 'POST':
            $body = json_decode(file_get_contents('php://input'), true);
            if (empty($body['email']) || empty($body['subject']) || empty($body['message'])) {
                throw new Exception('Missing required fields: email, subject, message', 422);
            }

            $errors = [];
            $ticket = Ticket::create([
                'name' => $body['name'] ?? '',
                'email' => $body['email'],
                'subject' => $body['subject'],
                'message' => $body['message'],
                'ip' => $_SERVER['REMOTE_ADDR'],
                'source' => 'API',
                'topicId' => $body['topic_id'] ?? null, 
            ], $errors, 'API');

            if (!$ticket) {
                throw new Exception('Failed to create ticket: ' . implode(', ', $errors), 500);
            }

            http_response_code(201);
            echo json_encode([
                'message' => 'Ticket created',
                'id' => $ticket->getId(),
                'number' => $ticket->getNumber(),
            ]);
            break;

        // Update a ticket by id
        case 'PUT':
        case 'PATCH':
            if (empty($_GET['id'])) throw new Exception('Missing ticket ID', 400);
            $ticket = Ticket::lookup((int)$_GET['id']);
            if (!$ticket) throw new Exception('Ticket not found', 404);

            $body = json_decode(file_get_contents('php://input'), true);
            if (!$body) throw new Exception('Invalid JSON body', 400);

            $updated = false;

            foreach ([
                'status_id' => 'setStatusId',
                'topic_id' => 'setTopicId'
            ] as $field => $setter) {
                if (isset($body[$field]) && method_exists($ticket, $setter)) {
                    $ticket->$setter($body[$field]);
                    $updated = true;
                }
            }

            if ($updated) $ticket->save();
            echo json_encode(['message' => 'Ticket updated']);
            break;

        // Delete a ticket by id
        case 'DELETE':
            if (empty($_GET['id'])) throw new Exception('Missing ticket ID', 400);
            $ticket = Ticket::lookup((int)$_GET['id']);
            if (!$ticket) throw new Exception('Ticket not found', 404);
            $ticket->delete();
            echo json_encode(['message' => 'Ticket deleted']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    $code = $e->getCode();
    if ($code < 100 || $code >= 600) $code = 500;
    http_response_code($code);
    echo json_encode(['error' => $e->getMessage()]);
}
