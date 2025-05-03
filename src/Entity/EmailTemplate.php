<?php

namespace App\Entity;

use App\Repository\EmailTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=EmailTemplateRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class EmailTemplate
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"template:read", "template:list"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50, unique=true)
     * @Groups({"template:read", "template:list"})
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=100)
     * @Groups({"template:read", "template:list"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"template:read", "template:list"})
     */
    private $subject;

    /**
     * @ORM\Column(type="string", length=100)
     * @Groups({"template:read"})
     */
    private $viewName;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"template:read", "template:list"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=50)
     * @Groups({"template:read", "template:list"})
     */
    private $category;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Groups({"template:read"})
     */
    private $metadata = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     * @Groups({"template:read", "template:list"})
     */
    private $tags = [];

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"template:read", "template:list"})
     */
    private $isActive = true;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"template:read", "template:list"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"template:read", "template:list"})
     */
    private $updatedAt;

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getViewName(): ?string
    {
        return $this->viewName;
    }

    public function setViewName(string $viewName): self
    {
        $this->viewName = $viewName;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    public function removeMetadata(string $key): self
    {
        if (isset($this->metadata[$key])) {
            unset($this->metadata[$key]);
        }

        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function addTag(string $tag): self
    {
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(string $tag): self
    {
        if (false !== $key = array_search($tag, $this->tags)) {
            unset($this->tags[$key]);
            $this->tags = array_values($this->tags);
        }

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
} 