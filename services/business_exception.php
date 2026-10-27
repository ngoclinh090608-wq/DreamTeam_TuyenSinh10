<?php
declare(strict_types=1);

/**
 * Lỗi nghiệp vụ: nội dung là thông báo hiển thị cho người dùng (theo alternative flow trong báo cáo).
 * details() chứa các lý do cụ thể để hiện thành danh sách bên dưới thông báo chính.
 */
class BusinessException extends RuntimeException
{
    /** @param string[] $details */
    public function __construct(string $message, private array $details = [])
    {
        parent::__construct($message);
    }

    /** @return string[] */
    public function details(): array { return $this->details; }
}
