<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $messages;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->messages = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        // 新しいクライアントが接続したときの処理
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        // 過去のメッセージを送信
        foreach ($this->messages as $message) {
            $conn->send($message);
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // クライアントからメッセージが届いたときの処理
        echo sprintf('Connection %d sending message "%s"' . "\n", $from->resourceId, $msg);

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // 送信元のクライアント以外の全クライアントにメッセージを送信
                $client->send($msg);
            }
        }

        // メッセージを保持
        $this->messages[] = $msg;
    }

    public function onClose(ConnectionInterface $conn) {
        // クライアントが切断されたときの処理
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        // エラーが発生したときの処理
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();