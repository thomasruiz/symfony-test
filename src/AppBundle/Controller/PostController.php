<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use AppBundle\Entity\Subscriber;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Post controller.
 *
 */
class PostController extends Controller
{
    /**
     * Lists all post entities.
     *
     * @param EntityManagerInterface $em
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(EntityManagerInterface $em)
    {
        $posts = $em->getRepository('AppBundle:Post')->findAll();
        $form = $this->createForm('AppBundle\Form\SubscriberType', null, [
            'action' => $this->generateUrl('subscriber_register')
        ]);

        return $this->render('post/index.html.twig', array(
            'posts' => $posts,
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a new post entity.
     *
     */
    public function newAction(Request $request)
    {
        $post = new Post();
        $form = $this->createForm('AppBundle\Form\PostType', $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $post->setCreatedAt(new DateTime());
            $em->persist($post);
            $em->flush();

            $this->notifySubscribers($post);

            return $this->redirectToRoute('post_show', array('id' => $post->getId()));
        }

        return $this->render('post/new.html.twig', array(
            'post' => $post,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a post entity.
     *
     */
    public function showAction(Post $post)
    {
        $deleteForm = $this->createDeleteForm($post);

        return $this->render('post/show.html.twig', array(
            'post' => $post,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing post entity.
     *
     */
    public function editAction(Request $request, Post $post)
    {
        $deleteForm = $this->createDeleteForm($post);
        $editForm = $this->createForm('AppBundle\Form\PostType', $post);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $post->setUpdatedAt(new DateTime());
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('post_edit', array('id' => $post->getId()));
        }

        return $this->render('post/edit.html.twig', array(
            'post' => $post,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a post entity.
     *
     */
    public function deleteAction(Request $request, Post $post)
    {
        $form = $this->createDeleteForm($post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($post);
            $em->flush();
        }

        return $this->redirectToRoute('post_index');
    }

    /**
     * Creates a form to delete a post entity.
     *
     * @param Post $post The post entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Post $post)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('post_delete', array('id' => $post->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    private function notifySubscribers(Post $post)
    {
        $em = $this->getDoctrine()->getManager();
        $subscribers = $em->getRepository('AppBundle:Subscriber')->findRegistered();

        if (count($subscribers)) {
            $mailer = $this->get('mailer');
            foreach ($subscribers as $subscriber) {
                $message = new \Swift_Message('A new post has arrived!');
                $message->setFrom('noreply@neoxia.com');
                $message->setTo($subscriber->getEmail());
                $message->setBody($this->renderView('emails/new_post.html.twig', ['post' => $post]));

                $mailer->send($message);
            }
        }
    }
}
