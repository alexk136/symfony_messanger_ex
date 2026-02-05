<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;
use App\Service\ChatService;


class WebsocketCommand extends Command
{
    private const OPERATION_TYPE_MESSAGE_SEND = 'message_send';
    private const OPERATION_TYPE_NEW_MESSAGE = 'new_message';
    private const OPERATION_TYPE_AUTH = 'auth';

    private $chatService;

    protected static $defaultName = 'websocket:server';

    private static $chatConnections = [];
    private static $connectionData = [];

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('WebSocket server')
             ->addArgument('action', InputArgument::REQUIRED, 'start|stop|restart');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');
        
        global $argv;
        $argv = ['websocket:server', $action];
        
        $worker = new Worker("websocket://0.0.0.0:8080"); // we can use ChannelServer.. but for tests 1 worker enougth
        $worker->count = 1;
        $worker->onWorkerStart = function() use ($output) {
            $output->writeln("WebSocket server started on ws://0.0.0.0:8080");
        };
        
        $worker->onConnect = function(TcpConnection $connection) {
            self::$connectionData[$connection->id] = $connection;

            echo "New connection: {$connection->id}\n";
        };
        
        $worker->onMessage = function(TcpConnection $connection, $data) {
            $message = json_decode($data, true);
   
            if (!isset(self::$connectionData[$connection->id])) {
                $connection->send(json_encode(['error' => 'Not authenticated']));
                return;
            }

            if ($message['type'] === self::OPERATION_TYPE_NEW_MESSAGE) {
                $this->handleMessage($connection, $message);
                return;
            }

            if ($message['type'] == self::OPERATION_TYPE_AUTH) {
                if (!isset(self::$chatConnections[$message['chat_id']])) {
                    self::$chatConnections[$message['chat_id']] = [];
                }
                self::$chatConnections[$message['chat_id']][$connection->id] = $connection;
            }            
        };
        
        $worker->onClose = function(TcpConnection $connection) {
            echo "Connection closed: {$connection->id}\n";
        };
        
        Worker::runAll();
        
        return Command::SUCCESS;
    }

    private function handleMessage(TcpConnection $connection, array $message)
    {
        $chatMessage = $this->chatService->sendMessage($message['chat_id'], $message['user_id'], $message['message'], $message['client_msg_id']);

        $connection->send(json_encode([
            'type' => self::OPERATION_TYPE_MESSAGE_SEND,
            'status' => 'success',
        ]));

        $this->broadcastToChat($message['chat_id'], [
            'type' => self::OPERATION_TYPE_NEW_MESSAGE,
            'chatId' => $message['chat_id'],
            'message' => [
                'id' => $chatMessage->getId(),
                'text' => $chatMessage->getText(),
                'createdAt' => $chatMessage->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    private function broadcastToChat(int $chatId, array $data)
    {
        if (!isset(self::$chatConnections[$chatId])) {
            return;
        }
        
        $json = json_encode($data);
        foreach (self::$chatConnections[$chatId] as $conn) {
            $conn->send($json);
        }
    }
}