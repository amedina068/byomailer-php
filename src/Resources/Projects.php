<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\CursorPage;
use Byomailer\Data\Project;
use Generator;

/**
 * The projects of your client.
 */
final readonly class Projects extends Resource
{
    /**
     * One page of projects, newest first, with the counts.
     *
     * @return CursorPage<Project>
     */
    public function list(?int $perPage = null, ?string $cursor = null): CursorPage
    {
        $body = $this->transport->get('projects', self::filled(['per_page' => $perPage, 'cursor' => $cursor]))->json();

        return CursorPage::fromBody($body, Project::fromArray(...));
    }

    /**
     * @return Generator<int, Project>
     */
    public function all(?int $perPage = null): Generator
    {
        return CursorPage::walk(fn (?string $cursor): CursorPage => $this->list($perPage, $cursor));
    }

    public function find(int $projectId): Project
    {
        return Project::fromArray($this->transport->get("projects/{$projectId}")->data());
    }

    /**
     * @param  string  $name  Unique in your client. The comparison ignores case.
     */
    public function create(string $name): Project
    {
        return Project::fromArray($this->transport->post('projects', ['name' => $name])->data());
    }

    public function rename(int $projectId, string $name): Project
    {
        return Project::fromArray($this->transport->patch("projects/{$projectId}", ['name' => $name], idempotent: true)->data());
    }

    public function delete(int $projectId): void
    {
        $this->transport->delete("projects/{$projectId}");
    }
}
