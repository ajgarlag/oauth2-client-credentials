<?php

namespace App\Command;

use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand('app:oauth2:client')]
class OAuth2ClientCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('base_uri', InputArgument::OPTIONAL, 'The base URI of the OAuth2 server', 'https://localhost:8000')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!$io->confirm('Do you want to fetch the access token using client credentials?')) {
            return Command::SUCCESS;
        }

        $baseUri = (string) $input->getArgument('base_uri');
        while(str_ends_with($baseUri, '/')) {
            $baseUri = substr($baseUri, 0, -1);
        }

        $scopedHttpClient = $this->httpClient->withOptions(['base_uri' => $baseUri]);

        $response = $scopedHttpClient->request('POST', '/token', ['body' => ['grant_type' => 'client_credentials', 'client_id' => 'test', 'client_secret' => 'test']]);
        $token = $response->toArray()['access_token'];
        $io->success('The access token is: ' . $token);



        if (!$io->confirm('Do you want to use the access token to fetch the server timestamp?')) {
            return Command::SUCCESS;
        }


        $response = $scopedHttpClient->request('GET', '/', ['headers' => ['Authorization' => 'Bearer ' . $token]]);

        try {
            $timestamp = $response->toArray()['time'];
        } catch (HttpExceptionInterface $e) {
            $io->error('The server returned an error: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $io->success('The server timestamp is: ' . DateTimeImmutable::createFromFormat('U', $timestamp)->format(DateTimeImmutable::ATOM));

        return Command::SUCCESS;
    }
}
