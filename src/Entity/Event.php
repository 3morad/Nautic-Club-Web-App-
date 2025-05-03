<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EventRepository::class)
 */
class Event
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     */
    private $eventDate;

    /**
     * @ORM\ManyToOne(targetEntity=LocationWeather::class, inversedBy="events")
     * @ORM\JoinColumn(nullable=false)
     */
    private $locationWeather;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $imageFilename;

    /**
     * @ORM\OneToMany(targetEntity=EventRegistration::class, mappedBy="event", orphanRemoval=true)
     */
    private $registrations;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $capacity;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;

    public function __construct()
    {
        $this->registrations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeInterface $eventDate): self
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function getLocationWeather(): ?LocationWeather
    {
        return $this->locationWeather;
    }

    public function setLocationWeather(?LocationWeather $locationWeather): self
    {
        $this->locationWeather = $locationWeather;

        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): self
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }

    /**
     * @return Collection<int, EventRegistration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(EventRegistration $registration): self
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setEvent($this);
        }

        return $this;
    }

    public function removeRegistration(EventRegistration $registration): self
    {
        if ($this->registrations->removeElement($registration)) {
            // set the owning side to null (unless already changed)
            if ($registration->getEvent() === $this) {
                $registration->setEvent(null);
            }
        }

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): self
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getRegistrationCount(): int
    {
        // Filter out cancelled registrations and sum the quantities
        $count = 0;
        foreach ($this->registrations as $registration) {
            if ($registration->getPaymentStatus() !== 'cancelled') {
                $count += $registration->getQuantity();
            }
        }
        
        return $count;
    }

    public function isFull(): bool
    {
        if ($this->capacity === null || $this->capacity <= 0) {
            return false; // No capacity limit
        }
        
        return $this->getRegistrationCount() >= $this->capacity;
    }

    public function getAvailableSpots(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }
        
        $available = $this->capacity - $this->getRegistrationCount();
        return max(0, $available);
    }

    public function getPaidRegistrationsCount(): int
    {
        $count = 0;
        foreach ($this->registrations as $registration) {
            if ($registration->getPaymentStatus() === 'paid') {
                $count += $registration->getQuantity();
            }
        }
        
        return $count;
    }
} 