<?php

namespace App\EmailSystem\Service;

use App\Entity\EmailTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class TemplateManager
{
    private $entityManager;
    private $twig;
    private $logger;
    private $emailTemplatesPath;

    public function __construct(
        EntityManagerInterface $entityManager,
        Environment $twig,
        LoggerInterface $logger,
        string $emailTemplatesPath = null
    ) {
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->emailTemplatesPath = $emailTemplatesPath ?? 'email/templates';
    }

    /**
     * Get all email templates
     *
     * @return EmailTemplate[]
     */
    public function getAllTemplates(): array
    {
        return $this->entityManager->getRepository(EmailTemplate::class)->findAll();
    }

    /**
     * Get a template by its code
     */
    public function getTemplateByCode(string $code): ?EmailTemplate
    {
        return $this->entityManager->getRepository(EmailTemplate::class)->findOneBy(['code' => $code]);
    }

    /**
     * Create a new email template
     */
    public function createTemplate(
        string $code,
        string $name,
        string $subject,
        string $viewName,
        ?string $description = null,
        string $category = 'general',
        ?array $tags = [],
        ?array $metadata = [],
        bool $isActive = true
    ): EmailTemplate {
        $template = new EmailTemplate();
        $template->setCode($code);
        $template->setName($name);
        $template->setSubject($subject);
        $template->setViewName($viewName);
        $template->setDescription($description);
        $template->setCategory($category);
        
        if (!empty($tags)) {
            $template->setTags($tags);
        }
        
        if (!empty($metadata)) {
            $template->setMetadata($metadata);
        }
        
        $template->setIsActive($isActive);
        
        // The entity lifecycle callbacks will set created/updated timestamps

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $template;
    }

    /**
     * Update an existing template
     */
    public function updateTemplate(
        EmailTemplate $template,
        array $data
    ): EmailTemplate {
        if (isset($data['name'])) {
            $template->setName($data['name']);
        }

        if (isset($data['subject'])) {
            $template->setSubject($data['subject']);
        }

        if (isset($data['view_name'])) {
            $template->setViewName($data['view_name']);
        }
        
        if (isset($data['description'])) {
            $template->setDescription($data['description']);
        }

        if (isset($data['category'])) {
            $template->setCategory($data['category']);
        }
        
        if (isset($data['tags'])) {
            $template->setTags($data['tags']);
        }
        
        if (isset($data['metadata'])) {
            $template->setMetadata($data['metadata']);
        }
        
        if (isset($data['is_active'])) {
            $template->setIsActive((bool)$data['is_active']);
        }
        
        // The entity lifecycle callbacks will update the updated timestamp

        $this->entityManager->flush();

        return $template;
    }

    /**
     * Delete a template
     */
    public function deleteTemplate(EmailTemplate $template): bool
    {
        try {
            $this->entityManager->remove($template);
            $this->entityManager->flush();
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete email template', [
                'template_code' => $template->getCode(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Preview a template with sample data
     */
    public function previewTemplate(EmailTemplate $template, array $context = []): string
    {
        $viewName = $template->getViewName();
        
        // Ensure we have some default context values for preview
        $defaultContext = [
            'subject' => $template->getSubject(),
            'user' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john.doe@example.com',
            ],
            'company' => [
                'name' => 'Nautic Club',
                'website' => 'https://nautic-club.example.com',
                'email' => 'contact@nautic-club.example.com',
                'phone' => '+1234567890',
            ],
            'date' => new \DateTime(),
            'template' => $template,
            'is_preview' => true,
        ];
        
        // Merge with any provided context
        $mergedContext = array_merge($defaultContext, $context);
        
        try {
            $templatePath = $this->emailTemplatesPath . '/' . $viewName;
            return $this->twig->render($templatePath, $mergedContext);
        } catch (\Exception $e) {
            // If template doesn't exist, return a helpful error message
            return '<div class="alert alert-danger">
                <h4>Template Error</h4>
                <p>The template file "' . htmlspecialchars($templatePath) . '" could not be rendered.</p>
                <p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>
            </div>';
        }
    }
    
    /**
     * Find templates by tag
     */
    public function findTemplatesByTag(string $tag): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        return $qb->select('t')
            ->from(EmailTemplate::class, 't')
            ->where('t.isActive = :active')
            ->andWhere('t.tags LIKE :tag')
            ->setParameter('active', true)
            ->setParameter('tag', '%"' . $tag . '"%')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find templates by multiple tags (all tags must be present)
     */
    public function findTemplatesByTags(array $tags): array
    {
        if (empty($tags)) {
            return [];
        }
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('t')
            ->from(EmailTemplate::class, 't')
            ->where('t.isActive = :active')
            ->setParameter('active', true);
            
        foreach ($tags as $index => $tag) {
            $qb->andWhere("t.tags LIKE :tag{$index}")
               ->setParameter("tag{$index}", '%"' . $tag . '"%');
        }
        
        return $qb->orderBy('t.name', 'ASC')
                 ->getQuery()
                 ->getResult();
    }
    
    /**
     * Clone a template
     */
    public function cloneTemplate(EmailTemplate $template, string $newCode): EmailTemplate
    {
        if ($this->getTemplateByCode($newCode)) {
            throw new \InvalidArgumentException("A template with code '$newCode' already exists");
        }
        
        return $this->createTemplate(
            $newCode,
            $template->getName() . ' (Copy)',
            $template->getSubject(),
            $template->getViewName(),
            $template->getDescription(),
            $template->getCategory(),
            $template->getTags(),
            $template->getMetadata(),
            $template->isActive()
        );
    }
    
    /**
     * Add a tag to a template
     */
    public function addTagToTemplate(EmailTemplate $template, string $tag): EmailTemplate
    {
        $template->addTag($tag);
        $this->entityManager->flush();
        
        return $template;
    }
    
    /**
     * Remove a tag from a template
     */
    public function removeTagFromTemplate(EmailTemplate $template, string $tag): EmailTemplate
    {
        $template->removeTag($tag);
        $this->entityManager->flush();
        
        return $template;
    }
} 