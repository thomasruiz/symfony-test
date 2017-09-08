<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Subscriber;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SubscriberController extends Controller
{
    public function registerAction(Request $request)
    {
        $subscriber = new Subscriber();
        $form = $this->createForm('AppBundle\Form\SubscriberType', $subscriber);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $subscriber->setCreatedAt(new DateTime());
            $em->persist($subscriber);
            $em->flush();
        }

        return $this->redirectToRoute('post_index');
    }
}
