<?php

namespace Tests\Unit;

use App\Support\BookingStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingStatusTest extends TestCase
{
    #[Test]
    public function it_includes_no_show_in_all_and_labels(): void
    {
        $this->assertContains(BookingStatus::NO_SHOW, BookingStatus::all());
        $this->assertSame('عدم حضور', BookingStatus::label(BookingStatus::NO_SHOW));
    }

    #[Test]
    public function no_show_does_not_hold_a_slot(): void
    {
        $this->assertNotContains(BookingStatus::NO_SHOW, BookingStatus::holding());
        $this->assertNotContains(BookingStatus::CANCELLED, BookingStatus::holding());
        $this->assertNotContains(BookingStatus::DONE, BookingStatus::holding());
    }

    #[Test]
    public function scheduled_and_confirmed_can_transition_to_no_show(): void
    {
        $this->assertTrue(BookingStatus::canTransition(BookingStatus::SCHEDULED, BookingStatus::NO_SHOW));
        $this->assertTrue(BookingStatus::canTransition(BookingStatus::CONFIRMED, BookingStatus::NO_SHOW));
        $this->assertTrue(BookingStatus::canTransition(BookingStatus::WAITING, BookingStatus::NO_SHOW));
        $this->assertFalse(BookingStatus::canTransition(BookingStatus::IN_CONSULT, BookingStatus::NO_SHOW));
        $this->assertTrue(BookingStatus::canTransition(BookingStatus::NO_SHOW, BookingStatus::WAITING));
    }
}
