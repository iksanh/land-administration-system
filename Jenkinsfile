// CI/CD pipeline: build the Laravel app on the Jenkins (WSL) agent, then deploy
// it to shared hosting over SSH (passwordless key — ssh-copy-id already done).
//
// Job setup: create a "Pipeline" job → "Pipeline script from SCM" → this repo,
// Script Path = Jenkinsfile. Fill the parameters on the first "Build with
// Parameters" run; Jenkins remembers them for subsequent builds.
//
// Agent prerequisites (install once in WSL): php, composer, node + npm, rsync,
// ssh/openssh-client. Jenkins plugins: Git, Pipeline, SSH Agent.
//
// IMPORTANT: vendor/ is resolved against symfony/laravel deps that require
// PHP >= 8.4.1, so the SHARED HOST must run PHP >= 8.4.1 too. Set PHP_BIN to the
// matching binary (e.g. /usr/local/bin/ea-php84 on cPanel).

pipeline {
    agent any

    parameters {
        string(name: 'DEPLOY_HOST', defaultValue: '', description: 'Shared-hosting hostname or IP (e.g. server123.webhost.com)')
        string(name: 'DEPLOY_USER', defaultValue: '', description: 'SSH / cPanel username')
        string(name: 'DEPLOY_PATH', defaultValue: '/home/USER/laravel_app', description: 'Absolute path to the Laravel app root on the server (kept ABOVE public_html)')
        string(name: 'PHP_BIN',     defaultValue: 'php', description: 'PHP CLI on the server — must be >= 8.4.1 (e.g. php, php8.4, /usr/local/bin/ea-php84)')
        string(name: 'SSH_CRED_ID', defaultValue: 'shared-hosting-ssh', description: 'Jenkins credentials ID — "SSH Username with private key" holding the key you ssh-copy-id\'d')
        booleanParam(name: 'RUN_MIGRATIONS',   defaultValue: true,  description: 'Run "php artisan migrate --force" after deploy')
        booleanParam(name: 'MAINTENANCE_MODE', defaultValue: true,  description: 'Put the site in maintenance mode during the release step')
    }

    options {
        timestamps()
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }

    environment {
        // accept-new = trust the host key the first time, then pin it (no prompt, no blind MITM).
        SSH_OPTS = '-o StrictHostKeyChecking=accept-new'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Verify tooling') {
            steps {
                sh '''
                    set -e
                    echo "PHP:      $(php -v | head -1)"
                    echo "Composer: $(composer --version)"
                    echo "Node:     $(node -v)"
                    echo "npm:      $(npm -v)"
                    echo "rsync:    $(rsync --version | head -1)"
                '''
            }
        }

        stage('Build: Composer (no-dev)') {
            steps {
                // --ignore-platform-req=php: the agent runs PHP 8.4.0 but deps want
                // 8.4.1; the produced vendor/ is valid on the 8.4.1+ server.
                sh '''
                    set -e
                    composer install --no-dev --optimize-autoloader --no-interaction \
                        --prefer-dist --no-progress --ignore-platform-req=php
                '''
            }
        }

        stage('Build: Frontend assets') {
            steps {
                sh '''
                    set -e
                    npm ci
                    npm run build
                '''
            }
        }

        stage('Deploy: rsync to server') {
            steps {
                sshagent(credentials: [params.SSH_CRED_ID]) {
                    // --delete keeps the server in sync with the build, but the
                    // excludes below are NEVER touched on the server: the real
                    // .env, runtime storage/, the public/storage symlink, etc.
                    sh """
                        set -e
                        rsync -az --delete \
                            --exclude='.git' \
                            --exclude='.github' \
                            --exclude='node_modules' \
                            --exclude='tests' \
                            --exclude='/.env' \
                            --exclude='/storage' \
                            --exclude='/public/storage' \
                            --exclude='/public/hot' \
                            --exclude='Jenkinsfile' \
                            -e "ssh ${SSH_OPTS}" \
                            ./ ${params.DEPLOY_USER}@${params.DEPLOY_HOST}:${params.DEPLOY_PATH}/
                    """
                }
            }
        }

        stage('Release: migrate & cache') {
            steps {
                script {
                    def down    = params.MAINTENANCE_MODE ? "${params.PHP_BIN} artisan down --retry=15 || true" : "true"
                    def migrate = params.RUN_MIGRATIONS   ? "${params.PHP_BIN} artisan migrate --force"          : "echo 'migrations skipped'"

                    // route:cache fails on this app's closure routes (/, /logout),
                    // so fall back to route:clear — config + view caches still apply.
                    def remote = """
                        set -e
                        cd '${params.DEPLOY_PATH}'
                        ${down}
                        ${migrate}
                        ${params.PHP_BIN} artisan storage:link || true
                        ${params.PHP_BIN} artisan config:cache
                        ${params.PHP_BIN} artisan view:cache
                        ${params.PHP_BIN} artisan route:cache || ${params.PHP_BIN} artisan route:clear
                        ${params.PHP_BIN} artisan up || true
                        echo 'Release complete.'
                    """.stripIndent()

                    writeFile file: 'deploy_remote.sh', text: remote
                    sshagent(credentials: [params.SSH_CRED_ID]) {
                        // Pipe the script over stdin to a login shell so the server's
                        // PATH (and the right PHP) is in scope — avoids quoting hell.
                        sh "ssh ${SSH_OPTS} ${params.DEPLOY_USER}@${params.DEPLOY_HOST} 'bash -ls' < deploy_remote.sh"
                    }
                }
            }
        }
    }

    post {
        success {
            echo "✅ Deployed to ${params.DEPLOY_USER}@${params.DEPLOY_HOST}:${params.DEPLOY_PATH}"
        }
        failure {
            // Best-effort: bring the site back up if a failed release left it down.
            script {
                if (params.DEPLOY_HOST?.trim()) {
                    sshagent(credentials: [params.SSH_CRED_ID]) {
                        sh """
                            ssh ${SSH_OPTS} ${params.DEPLOY_USER}@${params.DEPLOY_HOST} \
                                "cd '${params.DEPLOY_PATH}' && ${params.PHP_BIN} artisan up || true" || true
                        """
                    }
                }
            }
            echo "❌ Build failed — site brought back up if it was in maintenance mode."
        }
        always {
            sh 'rm -f deploy_remote.sh || true'
        }
    }
}
