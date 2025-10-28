// Este é o Jenkinsfile para o projeto Laravel (report-api)

pipeline {
    agent any // Executa em qualquer 'agente' (máquina) disponível no Jenkins

    // Variáveis de ambiente
    environment {
        PROJECT_DIR = '/var/www/report-api'

        NODE_BIN_PATH = '/home/ubuntu/.nvm/versions/node/v20.19.5/bin/node'
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
                // Como o Jenkins está no mesmo servidor,
                // executamos os comandos localmente.
                dir(PROJECT_DIR) {
                    script {
                        echo "A iniciar o deploy no diretório: ${PROJECT_DIR}"

                        // 1. Entrar em modo de manutenção
                        sh "php artisan down"

                        // 2. Instalar dependências (sem dev)
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

                        // **A CORREÇÃO ESTÁ AQUI:**
                        // Removemos o '\' antes do '.'
                        sh '''
                            # Define o NVM_DIR para o diretório do usuário 'ubuntu'
                            export NVM_DIR="/home/ubuntu/.nvm"

                            [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"

                            # Ativa a versão 20 (que tem o pm2)
                            nvm use 20

                            # Agora o 'pm2' deve estar no PATH
                            echo "A executar o comando PM2..."
                            pm2 restart laravel-queue-worker 2>/dev/null || pm2 start "php artisan queue:work --sleep=3 --tries=3" --name "laravel-queue-worker"
                        '''

                        echo "🚀 Deploy da API concluído!"
                    }
                }
            }
        }
    }

    post {
        // Acontece sempre no final, quer falhe ou tenha sucesso
        always {
            echo 'Limpeza... (se necessário)'
        }
    }
}
