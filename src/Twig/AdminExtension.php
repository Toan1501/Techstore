<?php

namespace App\Twig;

use App\Entity\Contact;
use App\Entity\Order;
use App\Entity\Question;
use App\Entity\SanPham;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/** Small counters for the admin sidebar badges ("việc cần xử lý"). Only called from the admin layout. */
class AdminExtension extends AbstractExtension
{
    private ?array $counts = null;

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('admin_counts', [$this, 'counts'])];
    }

    /** @return array{pending_orders: int, open_questions: int, contacts: int, low_stock: int} */
    public function counts(): array
    {
        if ($this->counts !== null) {
            return $this->counts;
        }

        $count = fn (string $dql, array $params = []) => (int) $this->em->createQuery($dql)->setParameters($params)->getSingleScalarResult();

        return $this->counts = [
            'pending_orders' => $count('SELECT COUNT(o.id) FROM ' . Order::class . ' o WHERE o.status = :s', ['s' => Order::STATUS_PENDING]),
            'open_questions' => $count('SELECT COUNT(q.id) FROM ' . Question::class . " q WHERE q.answer IS NULL OR q.answer = ''"),
            'contacts' => $count('SELECT COUNT(c.id) FROM ' . Contact::class . ' c'),
            'low_stock' => $count('SELECT COUNT(p.id) FROM ' . SanPham::class . ' p WHERE p.stock <= 5'),
        ];
    }
}
