// Este é o Jenkinsfile para o projeto Laravel (report-api)

pipeline {
    agent any // Executa em qualquer 'agente' (máquina) disponível no Jenkins

    // Variáveis de ambiente
    environment {
        // Define o diretório exato do projeto no servidor
        PROJECT_DIR = '/var/www/report-api'
    }

    stages {
        // --- Fase 1: Obter o Código (CI) ---
        stage('Checkout') {
            steps {
                // O Jenkins já faz o checkout automaticamente, mas isto garante
                echo "A obter o código mais recente..."
                // Limpa o espaço de trabalho antes de obter o código
                cleanWs()
                // Obtém o código (já configurado na UI do Jenkins)
                checkout scm
            }
        }

        // --- Fase 2: Testes e Lint (CI) ---
        // (Nota: Isto requer que o Jenkins tenha PHP, Composer, etc., instalados,
        // ou que usemos Docker. Para simplificar, vamos focar-nos no Deploy)
        stage('Test & Lint (Simulado)') {
            steps {
                // Num cenário real, aqui correria o PHPUnit e o Pint.
                echo "A simular testes e lint..."
                // sh './vendor/bin/pint --test'
                // sh './vendor/bin/phpunit'
            }
        }

        // --- Fase 3: Deploy (CD) ---
        stage('Deploy') {
            steps {
                // Como o Jenkins está no mesmo servidor,
                // executamos os comandos localmente.
                dir(PROJECT_DIR) {
                    script {
                        echo "A iniciar o deploy no diretório: ${PROJECT_DIR}"

                        // 1. Entrar em modo de manutenção
                        sh "php artisan down"

                        // 2. O 'checkout scm' no início já fez o 'git pull'

                        // 3. Instalar dependências (sem dev)
                        sh "rm -rf vendor/"
                        sh "composer install --no-dev --optimize-autoloader"

                        // 4. Migrações e Caches
                        sh "php artisan migrate --force"
                        sh "php artisan config:cache"
                        sh "php artisan route:cache"
                        sh "php artisan view:cache"

                        // 5. Reiniciar a fila (Sinaliza ao PM2)
                        sh "php artisan queue:restart"

                        // 6. Sair do modo de manutenção
                        sh "php artisan up"

                        // 7. Garantir que o worker PM2 está a correr
                        echo "A reiniciar o Laravel Queue Worker com PM2..."

                        sh 'pm2 restart laravel-queue-worker 2>/dev/null || pm2 start "php artisan queue:work --sleep=3 --tries=3" --name "laravel-queue-worker"'

                        echo "🚀 Deploy da API concluído!"
                    }
                }
            }
        }
    }

    post {
        always {
            echo 'Limpeza... (se necessário)'
        }
    }
}
