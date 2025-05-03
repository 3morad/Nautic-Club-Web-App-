<?php

namespace App\EmailSystem\Controller;

use App\Entity\EmailTemplate;
use App\EmailSystem\Service\TemplateManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for managing email templates
 */
#[Route('/email/templates')]
class EmailTemplateController extends AbstractController
{
    private $templateManager;
    private $entityManager;

    public function __construct(
        TemplateManager $templateManager,
        EntityManagerInterface $entityManager
    ) {
        $this->templateManager = $templateManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Display the templates index page
     */
    #[Route('/', name: 'email_template_index')]
    public function index(Request $request): Response
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $category = $request->query->get('category');
        $searchTerm = $request->query->get('search');

        if (!empty($searchTerm)) {
            $templates = $this->entityManager->getRepository(EmailTemplate::class)->search($searchTerm);
        } elseif (!empty($category)) {
            $templates = $this->entityManager->getRepository(EmailTemplate::class)->findByCategory($category);
        } else {
            $templates = $this->entityManager->getRepository(EmailTemplate::class)->findAll();
        }

        $categories = $this->entityManager->getRepository(EmailTemplate::class)->getCategoriesWithCount();

        return $this->render('@EmailSystem/templates/index.html.twig', [
            'templates' => $templates,
            'categories' => $categories,
            'currentCategory' => $category,
            'searchTerm' => $searchTerm,
        ]);
    }

    /**
     * Create a new email template
     */
    #[Route('/new', name: 'email_template_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            $code = $request->request->get('code');
            $name = $request->request->get('name');
            $subject = $request->request->get('subject');
            $viewName = $request->request->get('view_name');
            $description = $request->request->get('description');
            $category = $request->request->get('category');
            $tags = $request->request->get('tags');
            $isActive = $request->request->getBoolean('is_active', true);
            
            // Parse tags if they exist
            $tagArray = [];
            if (!empty($tags)) {
                $tagArray = array_map('trim', explode(',', $tags));
            }

            // Validation
            if (empty($code) || empty($name) || empty($subject) || empty($viewName) || empty($category)) {
                $this->addFlash('error', 'All fields are required except description and tags.');
                return $this->redirectToRoute('email_template_new');
            }

            // Check if code is unique
            $existingTemplate = $this->entityManager->getRepository(EmailTemplate::class)->findOneBy(['code' => $code]);
            if ($existingTemplate) {
                $this->addFlash('error', 'A template with this code already exists.');
                return $this->redirectToRoute('email_template_new');
            }

            try {
                $template = $this->templateManager->createTemplate(
                    $code,
                    $name,
                    $subject,
                    $viewName,
                    $description,
                    $category
                );
                
                // Add tags if present
                if (!empty($tagArray)) {
                    $template->setTags($tagArray);
                    $this->entityManager->flush();
                }
                
                // Set active status
                $template->setIsActive($isActive);
                $this->entityManager->flush();

                $this->addFlash('success', 'Template created successfully.');
                return $this->redirectToRoute('email_template_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to create template: ' . $e->getMessage());
            }
        }

        return $this->render('@EmailSystem/templates/new.html.twig');
    }

    /**
     * Create a new template via AJAX
     */
    #[Route('/api/create', name: 'email_template_api_create', methods: ['POST'])]
    public function apiCreate(Request $request): JsonResponse
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get JSON data from request
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON data'], 400);
        }
        
        // Required fields validation
        $requiredFields = ['code', 'name', 'subject', 'view_name', 'category'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Missing required field: ' . $field
                ], 400);
            }
        }
        
        // Check if code is unique
        $existingTemplate = $this->entityManager->getRepository(EmailTemplate::class)->findOneBy(['code' => $data['code']]);
        if ($existingTemplate) {
            return new JsonResponse([
                'success' => false,
                'message' => 'A template with this code already exists.'
            ], 400);
        }
        
        try {
            $template = $this->templateManager->createTemplate(
                $data['code'],
                $data['name'],
                $data['subject'],
                $data['view_name'],
                $data['description'] ?? '',
                $data['category']
            );
            
            // Add tags if present
            if (!empty($data['tags']) && is_array($data['tags'])) {
                $template->setTags($data['tags']);
            }
            
            // Set metadata if present
            if (!empty($data['metadata']) && is_array($data['metadata'])) {
                $template->setMetadata($data['metadata']);
            }
            
            // Set active status
            if (isset($data['is_active'])) {
                $template->setIsActive((bool)$data['is_active']);
            }
            
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Template created successfully',
                'template' => [
                    'id' => $template->getId(),
                    'code' => $template->getCode(),
                    'name' => $template->getName(),
                    'tags' => $template->getTags()
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to create template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit an existing template
     */
    #[Route('/{id}/edit', name: 'email_template_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $template = $this->entityManager->getRepository(EmailTemplate::class)->find($id);

        if (!$template) {
            throw $this->createNotFoundException('Template not found');
        }

        if ($request->isMethod('POST')) {
            $data = [
                'name' => $request->request->get('name'),
                'subject' => $request->request->get('subject'),
                'view_name' => $request->request->get('view_name'),
                'description' => $request->request->get('description'),
                'category' => $request->request->get('category'),
            ];
            
            // Parse tags if they exist
            $tags = $request->request->get('tags');
            if (!empty($tags)) {
                $tagArray = array_map('trim', explode(',', $tags));
                $template->setTags($tagArray);
            } else {
                $template->setTags([]);
            }
            
            // Set active status
            $template->setIsActive($request->request->getBoolean('is_active', true));

            // Validation
            if (empty($data['name']) || empty($data['subject']) || empty($data['view_name']) || empty($data['category'])) {
                $this->addFlash('error', 'All fields are required except description and tags.');
                return $this->redirectToRoute('email_template_edit', ['id' => $id]);
            }

            try {
                $this->templateManager->updateTemplate($template, $data);
                $this->addFlash('success', 'Template updated successfully.');
                return $this->redirectToRoute('email_template_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to update template: ' . $e->getMessage());
            }
        }

        return $this->render('@EmailSystem/templates/edit.html.twig', [
            'template' => $template,
        ]);
    }

    /**
     * Delete a template
     */
    #[Route('/{id}/delete', name: 'email_template_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $template = $this->entityManager->getRepository(EmailTemplate::class)->find($id);

        if (!$template) {
            throw $this->createNotFoundException('Template not found');
        }

        // Check CSRF token
        if (!$this->isCsrfTokenValid('delete'.$template->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('email_template_index');
        }

        try {
            $result = $this->templateManager->deleteTemplate($template);
            
            if ($result) {
                $this->addFlash('success', 'Template deleted successfully.');
            } else {
                $this->addFlash('error', 'Failed to delete template.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete template: ' . $e->getMessage());
        }

        return $this->redirectToRoute('email_template_index');
    }

    /**
     * Preview a template
     */
    #[Route('/{id}/preview', name: 'email_template_preview', methods: ['GET'])]
    public function preview(int $id): Response
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $template = $this->entityManager->getRepository(EmailTemplate::class)->find($id);

        if (!$template) {
            throw $this->createNotFoundException('Template not found');
        }

        $htmlContent = $this->templateManager->previewTemplate($template);

        return new Response($htmlContent);
    }
    
    /**
     * Get all templates (API)
     */
    #[Route('/api/list', name: 'email_template_api_list', methods: ['GET'])]
    public function apiList(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $templates = $this->entityManager->getRepository(EmailTemplate::class)->findAll();
        $formattedTemplates = [];
        
        foreach ($templates as $template) {
            $formattedTemplates[] = [
                'id' => $template->getId(),
                'code' => $template->getCode(),
                'name' => $template->getName(),
                'subject' => $template->getSubject(),
                'category' => $template->getCategory(),
                'description' => $template->getDescription()
            ];
        }
        
        return new JsonResponse($formattedTemplates);
    }
    
    /**
     * Get a single template (API)
     */
    #[Route('/api/{code}', name: 'email_template_api_get', methods: ['GET'])]
    public function apiGet(string $code): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $template = $this->entityManager->getRepository(EmailTemplate::class)->findOneBy(['code' => $code]);
        
        if (!$template) {
            return new JsonResponse(['success' => false, 'message' => 'Template not found'], 404);
        }
        
        return new JsonResponse([
            'id' => $template->getId(),
            'code' => $template->getCode(),
            'name' => $template->getName(),
            'subject' => $template->getSubject(),
            'view_name' => $template->getViewName(),
            'category' => $template->getCategory(),
            'description' => $template->getDescription(),
            'created_at' => $template->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $template->getUpdatedAt()?->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * API to search templates by tags
     */
    #[Route('/api/search-by-tags', name: 'email_template_api_search_by_tags', methods: ['GET', 'POST'])]
    public function apiSearchByTags(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get tags from query param or request body
        $tags = [];
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            $tags = $data['tags'] ?? [];
        } else {
            $tagsParam = $request->query->get('tags');
            if ($tagsParam) {
                $tags = is_array($tagsParam) ? $tagsParam : explode(',', $tagsParam);
            }
        }
        
        if (empty($tags)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No tags provided'
            ], 400);
        }
        
        // Create query builder
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('t')
           ->from(EmailTemplate::class, 't')
           ->where('t.isActive = :active')
           ->setParameter('active', true);
           
        // Add conditions for each tag (template must contain ALL specified tags)
        foreach ($tags as $index => $tag) {
            $qb->andWhere("t.tags LIKE :tag{$index}")
               ->setParameter("tag{$index}", '%"' . $tag . '"%');
        }
        
        $templates = $qb->getQuery()->getResult();
        
        $result = [];
        foreach ($templates as $template) {
            $result[] = [
                'id' => $template->getId(),
                'code' => $template->getCode(),
                'name' => $template->getName(),
                'subject' => $template->getSubject(),
                'category' => $template->getCategory(),
                'tags' => $template->getTags(),
                'description' => $template->getDescription()
            ];
        }
        
        return new JsonResponse([
            'success' => true,
            'count' => count($result),
            'templates' => $result
        ]);
    }

    /**
     * Clone a template
     */
    #[Route('/{id}/clone', name: 'email_template_clone', methods: ['POST'])]
    public function cloneTemplate(int $id, Request $request): Response
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $template = $this->entityManager->getRepository(EmailTemplate::class)->find($id);

        if (!$template) {
            throw $this->createNotFoundException('Template not found');
        }
        
        $newCode = $request->request->get('new_code');
        
        if (empty($newCode)) {
            $newCode = $template->getCode() . '_copy_' . time();
        }
        
        try {
            $clonedTemplate = $this->templateManager->cloneTemplate($template, $newCode);
            $this->addFlash('success', 'Template cloned successfully.');
            return $this->redirectToRoute('email_template_edit', ['id' => $clonedTemplate->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to clone template: ' . $e->getMessage());
            return $this->redirectToRoute('email_template_index');
        }
    }
    
    /**
     * API Clone template
     */
    #[Route('/api/{id}/clone', name: 'email_template_api_clone', methods: ['POST'])]
    public function apiCloneTemplate(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $template = $this->entityManager->getRepository(EmailTemplate::class)->find($id);
        
        if (!$template) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }
        
        $data = json_decode($request->getContent(), true) ?? [];
        $newCode = $data['new_code'] ?? null;
        
        if (empty($newCode)) {
            $newCode = $template->getCode() . '_copy_' . time();
        }
        
        try {
            $clonedTemplate = $this->templateManager->cloneTemplate($template, $newCode);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Template cloned successfully',
                'template' => [
                    'id' => $clonedTemplate->getId(),
                    'code' => $clonedTemplate->getCode(),
                    'name' => $clonedTemplate->getName()
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to clone template: ' . $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Get templates by category (API)
     */
    #[Route('/api/category/{category}', name: 'email_template_api_by_category', methods: ['GET'])]
    public function apiGetByCategory(string $category): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $templates = $this->entityManager->getRepository(EmailTemplate::class)->findByCategory($category);
        
        $result = [];
        foreach ($templates as $template) {
            $result[] = [
                'id' => $template->getId(),
                'code' => $template->getCode(),
                'name' => $template->getName(),
                'subject' => $template->getSubject(),
                'category' => $template->getCategory(),
                'tags' => $template->getTags(),
                'description' => $template->getDescription(),
                'is_active' => $template->isActive()
            ];
        }
        
        return new JsonResponse([
            'success' => true,
            'count' => count($result),
            'category' => $category,
            'templates' => $result
        ]);
    }
    
    /**
     * Get all template categories (API)
     */
    #[Route('/api/categories', name: 'email_template_api_categories', methods: ['GET'])]
    public function apiGetCategories(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $categories = $this->entityManager->getRepository(EmailTemplate::class)->getAllCategories();
        $categoriesWithCount = $this->entityManager->getRepository(EmailTemplate::class)->getCategoriesWithCount();
        
        $result = [];
        foreach ($categories as $category) {
            $result[] = [
                'name' => $category,
                'count' => $categoriesWithCount[$category] ?? 0
            ];
        }
        
        return new JsonResponse([
            'success' => true,
            'count' => count($result),
            'categories' => $result
        ]);
    }
    
    /**
     * Add/Remove tags from a template (API)
     */
    #[Route('/api/{id}/tags', name: 'email_template_api_update_tags', methods: ['PATCH'])]
    public function apiUpdateTags(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $template = $this->entityManager->getRepository(EmailTemplate::class)->find($id);
        
        if (!$template) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }
        
        $data = json_decode($request->getContent(), true) ?? [];
        
        // Add tags
        if (!empty($data['add_tags']) && is_array($data['add_tags'])) {
            foreach ($data['add_tags'] as $tag) {
                $this->templateManager->addTagToTemplate($template, $tag);
            }
        }
        
        // Remove tags
        if (!empty($data['remove_tags']) && is_array($data['remove_tags'])) {
            foreach ($data['remove_tags'] as $tag) {
                $this->templateManager->removeTagFromTemplate($template, $tag);
            }
        }
        
        // Set entirely new tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $template->setTags($data['tags']);
            $this->entityManager->flush();
        }
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Tags updated successfully',
            'template_id' => $template->getId(),
            'tags' => $template->getTags()
        ]);
    }
} 