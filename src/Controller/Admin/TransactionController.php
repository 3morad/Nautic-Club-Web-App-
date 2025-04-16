<?php

namespace App\Controller\Admin;

use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/transactions')]
#[IsGranted('ROLE_ADMIN')]
class TransactionController extends AbstractController
{
    #[Route('/', name: 'admin_transactions_index', methods: ['GET'])]
    public function index(Request $request, TransactionRepository $transactionRepository, PaginatorInterface $paginator): Response
    {
        $query = $transactionRepository->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery();

        $transactions = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/transactions/index.html.twig', [
            'transactions' => $transactions,
        ]);
    }

    #[Route('/new', name: 'admin_transactions_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $transaction = new Transaction();
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // createdAt is already set in the Transaction constructor
            
            $entityManager->persist($transaction);
            $entityManager->flush();

            $this->addFlash('success', 'Transaction created successfully.');
            return $this->redirectToRoute('admin_transactions_index');
        }

        return $this->render('admin/transactions/form.html.twig', [
            'transaction' => $transaction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_transactions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Transaction updated successfully.');
            return $this->redirectToRoute('admin_transactions_index');
        }

        return $this->render('admin/transactions/form.html.twig', [
            'transaction' => $transaction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_transactions_delete', methods: ['POST'])]
    public function delete(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$transaction->getId(), $request->request->get('_token'))) {
            $entityManager->remove($transaction);
            $entityManager->flush();
            $this->addFlash('success', 'Transaction deleted successfully.');
        }

        return $this->redirectToRoute('admin_transactions_index');
    }
} 