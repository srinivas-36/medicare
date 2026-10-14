<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_three_services_and_the_services_page_lists_all_six(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['Cancer Care', 'Labor & Delivery', 'Heart & Vascular'])
            ->assertSee("World-class cancer care with compassion. From early detection to advanced treatment, we're with you every step of the journey.")
            ->assertSee('Personalized labor and delivery care, bringing new life into the world with confidence.')
            ->assertDontSee('Neurology')
            ->assertDontSee('Mental Health')
            ->assertDontSee('Burn Care')
            ->assertSee('View all services');

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Cancer Care',
                'Labor & Delivery',
                'Heart & Vascular',
                'Neurology',
                'Mental Health',
                'Burn Care',
            ])
            ->assertSee('images/cancer-care.jpg', false)
            ->assertSee('images/burn.webp', false);
    }
}
