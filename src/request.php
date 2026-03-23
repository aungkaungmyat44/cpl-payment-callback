<?php

class Request {
    private array $get;
    private array $post;
    private array $json;
    private array $server;

    private function __construct(array $get, array $post, array $json, array $server) {
        $this->get = $get;
        $this->post = $post;
        $this->json = $json;
        $this->server = $server;
    }

    public static function fromGlobals(): self {
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        if (!is_array($json)) $json = [];

        return new self($_GET, $_POST, $json, $_SERVER);
    }

    public function query(string $key, $default=null) {
        return $this->get[$key] ?? $default;
    }

    public function input(string $key, $default=null) {
        if (array_key_exists($key, $this->post)) return $this->post[$key];
        if (array_key_exists($key, $this->json)) return $this->json[$key];
        return $default;
    }

    public function getGetParams(): array {
        return $this->get;
    }

    public function getPostParams(): array {
        return $this->post;
    }

    public function getMethod(): string {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function getIp(): string {
        return $this->server['REMOTE_ADDR'] ?? '';
    }

    public function getUserAgent(): string {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function getUri(): string {
        return $this->server['REQUEST_URI'] ?? '';
    }

    public function getReferer(): string {
        return $this->server['HTTP_REFERER'] ?? '';
    }

    public function getRawBody(): string {
        return file_get_contents('php://input');
    }
}