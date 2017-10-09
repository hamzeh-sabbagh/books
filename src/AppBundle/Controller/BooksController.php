<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\HttpFoundation\Response;

class BooksController extends Controller
{

    public function indexAction(Request $request) {
        return $this->redirect('http://localhost:9200/data/book/_search');
    }

    public function addAction(Request $request) {

        $book = $request->getContent();

        if(empty($book)) {
            $response = new Response('Invalid Params!',Response::HTTP_NOT_FOUND);
            $response->headers->set('Content-Type', 'text/plain');

            return $response;
        }

        $connection = new AMQPStreamConnection( getenv('RMQ_HOST'), 
                                                getenv('RMQ_PORT'), 
                                                getenv('RMQ_USER'), 
                                                getenv('RMQ_PASS'));
        $channel = $connection->channel();

        $channel->queue_declare(getenv('RMQ_INDEX'), false, false, false, false);

        $msg = new AMQPMessage($book);
        $channel->basic_publish($msg, '', getenv('RMQ_INDEX'));

        $channel->close();
        $connection->close();

        $response = new Response('Success!',Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }

}
