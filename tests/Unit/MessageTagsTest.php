<?php

namespace Tests\Unit;

use App\Support\MessageTags;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageTagsTest extends TestCase
{
    #[Test]
    public function chips_include_person_and_booking_groups(): void
    {
        $chips = MessageTags::chips();

        $this->assertNotEmpty($chips['person']);
        $this->assertNotEmpty($chips['booking']);
        $this->assertContains('{نام}', array_column($chips['person'], 'token'));
        $this->assertContains('{تاریخ}', array_column($chips['booking'], 'token'));
        $this->assertContains('{نوع عمل}', array_column($chips['booking'], 'token'));
    }

    #[Test]
    public function booking_keys_cover_persian_and_times_aliases(): void
    {
        $keys = MessageTags::bookingKeys();

        $this->assertContains('تاریخ', $keys);
        $this->assertContains('date', $keys);
        $this->assertContains('بیمارستان', $keys);
        $this->assertContains('hospital', $keys);
        $this->assertContains('surgeryType', $keys);
    }
}
