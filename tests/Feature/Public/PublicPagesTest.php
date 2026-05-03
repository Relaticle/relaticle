<?php

declare(strict_types=1);

use App\Http\Controllers\Blog\BlogCategoryController;
use App\Http\Controllers\Blog\BlogFeedController;
use App\Http\Controllers\Blog\BlogPreviewController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\TermsOfServiceController;
use Illuminate\Support\Facades\Http;
use ManukMinasyan\FilamentBlog\Models\Category;
use ManukMinasyan\FilamentBlog\Models\Post;

mutates(HomeController::class, TermsOfServiceController::class, PrivacyPolicyController::class, BlogController::class, BlogCategoryController::class, BlogFeedController::class, BlogPreviewController::class);

beforeEach(function () {
    Http::fake([
        'api.github.com/*' => Http::response(['stargazers_count' => 42], 200),
    ]);
});

describe('Home page', function () {
    it('returns a successful response', function () {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Relaticle');
    });

    it('displays the GitHub stars count', function () {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('42');
    });
});

describe('Legal pages', function () {
    it('displays the terms of service page with product-specific content', function () {
        $response = $this->get('/terms-of-service');

        $response->assertStatus(200);
        $response->assertSee('Terms of Service');
        $response->assertSee('Relaticle');
        $response->assertDontSee('word usage');
        $response->assertDontSee('Basic" plan');
    });

    it('displays the privacy policy page with product-specific content', function () {
        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('Relaticle');
        $response->assertDontSee('registered mail');
    });
});

describe('Documentation pages', function () {
    it('displays the documentation index', function () {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $response->assertSee('Documentation');
    });

    it('displays the getting started guide', function () {
        $response = $this->get('/docs/getting-started');

        $response->assertStatus(200);
        $response->assertSee('Getting Started');
    });

    it('displays the import guide', function () {
        $response = $this->get('/docs/import');

        $response->assertStatus(200);
        $response->assertSee('Import Guide');
    });

    it('displays the developer guide', function () {
        $response = $this->get('/docs/developer');

        $response->assertStatus(200);
        $response->assertSee('Developer Guide');
    });

    it('displays the self-hosting guide', function () {
        $response = $this->get('/docs/self-hosting');

        $response->assertStatus(200);
        $response->assertSee('Self-Hosting Guide');
    });

    it('displays the MCP guide', function () {
        $response = $this->get('/docs/mcp');

        $response->assertStatus(200);
        $response->assertSee('MCP Server');
    });

    it('shows edit on GitHub link on documentation pages', function () {
        $response = $this->get('/docs/getting-started');

        $response->assertStatus(200);
        $response->assertSee('Edit this page on GitHub');
    });

    it('returns 404 for non-existent documentation page', function () {
        $response = $this->get('/docs/non-existent-page');

        $response->assertStatus(404);
    });

    it('can search documentation and returns results', function () {
        $response = $this->get('/docs/search?query=import');

        $response->assertStatus(200);
        $response->assertSee('Import');
    });
});

describe('Pricing page', function () {
    it('displays the pricing page', function () {
        $response = $this->get('/pricing');

        $response->assertStatus(200);
        $response->assertSee('No per-seat pricing');
    });
});

describe('Authentication redirects', function () {
    it('redirects login to app panel', function () {
        $response = $this->get('/login');

        $response->assertRedirect(url()->getAppUrl('login'));
    });

    it('redirects register to app panel', function () {
        $response = $this->get('/register');

        $response->assertRedirect(url()->getAppUrl('register'));
    });

    it('redirects forgot password to app panel', function () {
        $response = $this->get('/forgot-password');

        $response->assertRedirect(url()->getAppUrl('forgot-password'));
    });

    it('redirects dashboard to app panel', function () {
        $response = $this->get('/dashboard');

        $response->assertRedirect(url()->getAppUrl());
    });
});

describe('Community redirects', function () {
    it('redirects to discord', function () {
        config(['services.discord.invite_url' => 'https://discord.gg/example']);

        $response = $this->get('/discord');

        $response->assertRedirect('https://discord.gg/example');
    });
});

describe('Social authentication routes', function () {
    it('throttles authentication redirect attempts', function () {
        // Make 10 requests (the limit)
        for ($i = 0; $i < 10; $i++) {
            $this->get('/auth/redirect/github');
        }

        // The 11th request should be throttled
        $response = $this->get('/auth/redirect/github');

        $response->assertStatus(429); // Too Many Requests
    });

    it('accepts github as a provider for redirect', function () {
        $response = $this->get('/auth/redirect/github');

        $response->assertStatus(302); // Redirect to GitHub
    });

    it('accepts google as a provider for redirect', function () {
        $response = $this->get('/auth/redirect/google');

        $response->assertStatus(302); // Redirect to Google
    });
});

describe('Blog pages', function () {
    it('displays the blog index', function () {
        $this->get('/blog')
            ->assertStatus(200)
            ->assertSee('Engineering Blog');
    });

    it('displays published posts on the index', function () {
        $post = Post::factory()->create();

        $this->get('/blog')
            ->assertStatus(200)
            ->assertSee($post->title);
    });

    it('does not display draft posts on the index', function () {
        $post = Post::factory()->draft()->create();

        $this->get('/blog')
            ->assertStatus(200)
            ->assertDontSee($post->title);
    });

    it('displays a single blog post', function () {
        $post = Post::factory()->create();

        $this->get("/blog/{$post->slug}")
            ->assertStatus(200)
            ->assertSee($post->title);
    });

    it('returns 404 for non-existent blog post', function () {
        $this->get('/blog/non-existent-post')
            ->assertStatus(404);
    });

    it('displays posts filtered by category', function () {
        $category = Category::factory()->create();
        $post = Post::factory()->create(['category_id' => $category->id]);

        $this->get("/blog/category/{$category->slug}")
            ->assertStatus(200)
            ->assertSee($post->title)
            ->assertSee($category->name);
    });

    it('returns RSS feed', function () {
        Post::factory()->create();

        $this->get('/blog/feed')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/rss+xml');
    });

    it('includes blog link in navigation', function () {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee(route('blog.index'));
    });
});

describe('Error handling', function () {
    it('returns 404 for non-existent routes', function () {
        $response = $this->get('/non-existent-page');

        $response->assertStatus(404);
    });
});

describe('Response meta', function () {
    it('returns proper content type', function () {
        $response = $this->get('/');

        $response->assertHeader('Content-Type');
        $response->assertSuccessful();
    });
});
