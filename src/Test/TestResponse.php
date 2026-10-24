<?php

namespace Clicalmani\Core\Test;

use PHPUnit\Framework\Assert;
use Clicalmani\Core\Http\Response;

class TestResponse
{
    /**
     * The raw response instance.
     *
     * @var \Clicalmani\Core\Http\Response
     */
    protected Response $response;

    /**
     * The response content (decoded or raw).
     *
     * @var string
     */
    protected string $content;

    /**
     * The HTTP status code.
     *
     * @var int
     */
    protected int $status;

    /**
     * The response headers.
     *
     * @var array
     */
    protected array $headers;

    public function __construct(Response $response)
    {
        $this->response = $response;
        $this->status   = $response->getStatusCode();
        $this->headers  = $response->getHeaders();
        $this->content  = $response->getBody()->getContents();
    }

    /**
     * Get the HTTP status code.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Get the raw response content.
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * Get the decoded JSON payload.
     */
    public function json(?string $key = null)
    {
        $data = json_decode($this->content, true);

        if ($key === null) {
            return $data;
        }

        return data_get($data, $key);
    }

    /**
     * Get a response header.
     */
    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    // ---------------------------------------------------------------
    // Status assertions
    // ---------------------------------------------------------------

    public function assertStatus(int $status): static
    {
        Assert::assertSame(
            $status,
            $this->status,
            "Expected status code {$status}, got {$this->status}. Body: {$this->content}"
        );
        return $this;
    }

    public function assertOk(): static
    {
        return $this->assertStatus(200);
    }

    public function assertCreated(): static
    {
        return $this->assertStatus(201);
    }

    public function assertNoContent(): static
    {
        return $this->assertStatus(204);
    }

    public function assertNotFound(): static
    {
        return $this->assertStatus(404);
    }

    public function assertForbidden(): static
    {
        return $this->assertStatus(403);
    }

    public function assertUnauthorized(): static
    {
        return $this->assertStatus(401);
    }

    public function assertUnprocessable(): static
    {
        return $this->assertStatus(422);
    }

    public function assertRedirect(?string $uri = null): static
    {
        Assert::assertTrue(
            in_array($this->status, [301, 302, 303, 307, 308]),
            "Expected a redirect status, got {$this->status}."
        );

        if ($uri !== null) {
            Assert::assertSame($uri, $this->header('location'));
        }

        return $this;
    }

    // ---------------------------------------------------------------
    // Content assertions
    // ---------------------------------------------------------------

    public function assertSee(string $value): static
    {
        Assert::assertStringContainsString($value, $this->content);
        return $this;
    }

    public function assertDontSee(string $value): static
    {
        Assert::assertStringNotContainsString($value, $this->content);
        return $this;
    }

    public function assertJson(array $data): static
    {
        $actual = $this->json();

        foreach ($data as $key => $value) {
            Assert::assertSame(
                $value,
                data_get($actual, $key),
                "Failed asserting that JSON key [{$key}] equals the expected value."
            );
        }

        return $this;
    }

    public function assertJsonPath(string $path, mixed $expected): static
    {
        Assert::assertSame(
            $expected,
            data_get($this->json(), $path),
            "Failed asserting that JSON path [{$path}] equals the expected value."
        );
        return $this;
    }

    public function assertJsonCount(int $count, ?string $key = null): static
    {
        $data = $this->json();

        if ($key !== null) {
            $data = data_get($data, $key);
        }

        Assert::assertCount($count, $data);
        return $this;
    }

    public function assertJsonStructure(array $structure, ?array $data = null): static
    {
        $data ??= $this->json();

        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                Assert::assertIsArray($data);
                foreach ($data as $item) {
                    $this->assertJsonStructure($value, $item);
                }
            } elseif (is_array($value)) {
                Assert::assertArrayHasKey($key, $data ?? []);
                $this->assertJsonStructure($value, $data[$key]);
            } else {
                Assert::assertArrayHasKey($value, $data);
            }
        }

        return $this;
    }

    public function assertHeader(string $name, string $value): static
    {
        Assert::assertSame($value, $this->header($name));
        return $this;
    }
}