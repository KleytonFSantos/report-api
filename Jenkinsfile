// Este é o Jenkinsfile para o projeto Laravel (report-api)

pipeline {
    agent any // Executa em qualquer 'agente' (máquina) disponível no Jenkins

    environment {
        // Define o diretório exato do projeto no servidor
        PROJECT_DIR = '/var/www/report-api'

        // Define o caminho para o NVM (para o PM2 do worker)
        NVM_DIR = '/home/ubuntu/.nvm'
    }

    stages {
        // --- Fase 1: Obter o Código (CI) ---
        stage('Checkout') {
            steps {
                echo "A obter o código mais recente..."
                cleanWs()
                checkout scm
            }
        }

        // --- Fase 2: Testes e Lint (Simulado) ---
        stage('Test & Lint (Simulado)') {
            steps {
                echo "A simular testes e lint..."
            }
        }

        // --- Fase 3: Deploy (CD) ---
        stage('Deploy') {
            steps {
                dir(PROJECT_DIR) {
                    script {
                        echo "A iniciar o deploy no diretório: ${PROJECT_DIR}"

                        // 1. Entrar em modo de manutenção
                        sh "php artisan down"

                        // 2. Instalar dependências (sem dev)
                        sh "rm -rf vendor/"
                        sh "composer install --no-dev --optimize-autoloader"

                        // 3. Migrações e Caches
                        sh "php artisan migrate --force"
                        sh "php artisan config:cache"
                        sh "php artisan route:cache"
                        sh "php artisan view:cache"

                        // 4. CORREÇÃO: Definir permissões para o www-data (Nginx/PHP-FPM)
                        sh "sudo chown -R $USER:www-data storage bootstrap/cache"
                        sh "sudo chmod -R 775 storage bootstrap/cache"

                        // 5. Reiniciar a fila (Sinaliza ao PM2)
                        sh "php artisan queue:restart"

                        // 6. ADIÇÃO: Reiniciar o PHP-FPM para carregar o novo código
                        echo "A reiniciar o PHP-FPM..."
                        sh "sudo systemctl restart php8.3-fpm"

                        // 7. Sair do modo de manutenção
                        sh "php artisan up"

                        // 8. Garantir que o worker PM2 está a correr
                        echo "A reiniciar o Laravel Queue Worker com PM2..."
                        sh """
                            #!/bin/bash
                            export NVM_DIR="${env.NVM_DIR}"
                            [ -s "\$NVM_DIR/nvm.sh" ] && . "\$NVM_DIR/nvm.sh"
                            nvm use 20
                            pm2 restart laravel-queue-worker 2>/dev/null || pm2 start "php artisan queue:work --sleep=3 --tries=3" --name "laravel-queue-worker"
                        """

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
