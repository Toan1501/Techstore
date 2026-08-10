<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?SanPham $product = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank(message: 'Vui lòng nhập tên.')]
    private ?string $authorName = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Vui lòng nhập câu hỏi.')]
    private ?string $content = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $answer = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $answeredAt = null;

    public function getId(): ?int { return $this->id; }
    public function getProduct(): ?SanPham { return $this->product; }
    public function setProduct(?SanPham $p): static { $this->product = $p; return $this; }
    public function getAuthorName(): ?string { return $this->authorName; }
    public function setAuthorName(string $v): static { $this->authorName = $v; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $v): static { $this->content = $v; return $this; }
    public function getAnswer(): ?string { return $this->answer; }
    public function setAnswer(?string $v): static { $this->answer = $v; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $v): static { $this->createdAt = $v; return $this; }
    public function getAnsweredAt(): ?\DateTimeInterface { return $this->answeredAt; }
    public function setAnsweredAt(?\DateTimeInterface $v): static { $this->answeredAt = $v; return $this; }
    public function isAnswered(): bool { return $this->answer !== null && $this->answer !== ''; }
}
