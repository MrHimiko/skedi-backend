<?php

namespace App\Plugins\Widgets\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "widgets")]
class WidgetEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "widget_id", type: "bigint")]
    private int $id;

    #[ORM\Column(name: "widget_name", type: "string", length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: "widget_title", type: "string", length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: "widget_description", type: "string", length: 1000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: "widget_content", type: "json", nullable: true)]
    private ?array $content = null;

    #[ORM\Column(name: "widget_cover", type: "string", nullable: true)]
    private ?array $cover = null;

    #[ORM\Column(name: "widget_images", type: "string", nullable: true)]
    private ?array $images = null;

    #[ORM\Column(name: "widget_updated", type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTimeInterface $updated;

    #[ORM\Column(name: "widget_created", type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTimeInterface $created;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
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

    public function getContent(): ?array
    {
        return $this->content;
    }

    public function setContent(?array $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getCover(): ?array
    {
        return $this->cover;
    }

    public function setCover(?array $cover): self
    {
        $this->cover = $cover;
        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): self
    {
        $this->images = $images;
        return $this;
    }

    public function getUpdated(): \DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(\DateTimeInterface $updated): self
    {
        $this->updated = $updated;
        return $this;
    }

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'content' => $this->getContent(),
            'cover' => $this->getCover(),
            'images' => $this->getImages(),
            'updated' => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}
