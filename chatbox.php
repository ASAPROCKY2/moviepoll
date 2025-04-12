<?php
session_start(); // Start a session for user tracking
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoviePoll - Genre Voting System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #ef233c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 0.5rem;
            color: var(--accent);
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 1.5rem;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }

        nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .hero {
            text-align: center;
            padding: 4rem 0;
            background: url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center/cover;
            color: white;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.8rem;
            background-color: var(--accent);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background-color: #ff0676;
        }

        .features {
            padding: 4rem 0;
            background-color: white;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .section-title p {
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background-color: var(--light);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .feature-card p {
            color: var(--gray);
        }

        /* Chatbot Styles */
        #chat-icon {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        #chat-icon:hover {
            transform: scale(1.1);
            background-color: var(--secondary);
        }

        #chat-icon i {
            font-size: 1.8rem;
        }

        #chat-container {
            display: none;
            position: fixed;
            bottom: 5rem;
            right: 2rem;
            width: 350px;
            height: 500px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            flex-direction: column;
            z-index: 1000;
            overflow: hidden;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        #chat-container.active {
            transform: translateY(0);
            opacity: 1;
        }

        #chat-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #chat-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .close-chat {
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.2s ease;
        }

        .close-chat:hover {
            transform: scale(1.1);
        }

        #chat-box {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background-color: #f8f9fa;
        }

        .message {
            max-width: 80%;
            margin-bottom: 1rem;
            padding: 0.8rem 1rem;
            border-radius: 1rem;
            line-height: 1.4;
            position: relative;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .user-message {
            background-color: var(--primary);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 0.3rem;
        }

        .bot-message {
            background-color: white;
            color: var(--dark);
            margin-right: auto;
            border-bottom-left-radius: 0.3rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #user-input-container {
            display: flex;
            padding: 1rem;
            border-top: 1px solid #e9ecef;
            background-color: white;
        }

        #user-input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 1px solid #e9ecef;
            border-radius: 50px;
            outline: none;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        #user-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        #send-button {
            margin-left: 0.5rem;
            padding: 0.8rem 1.2rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #send-button:hover {
            background-color: var(--secondary);
        }

        .typing-indicator {
            display: flex;
            padding: 1rem;
        }

        .typing-indicator span {
            height: 8px;
            width: 8px;
            background-color: var(--gray);
            border-radius: 50%;
            display: inline-block;
            margin: 0 2px;
            animation: typing 1s infinite;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0% { transform: translateY(0); opacity: 0.4; }
            50% { transform: translateY(-5px); opacity: 1; }
            100% { transform: translateY(0); opacity: 0.4; }
        }

        footer {
            background-color: var(--dark);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .social-links {
            display: flex;
            justify-content: center;
            margin: 1rem 0;
        }

        .social-links a {
            color: white;
            margin: 0 0.5rem;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            color: var(--accent);
            transform: translateY(-3px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            nav ul {
                margin-top: 1rem;
                justify-content: center;
            }

            nav ul li {
                margin: 0 0.5rem;
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            #chat-container {
                width: 90%;
                right: 5%;
                bottom: 6rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">
                <i class='bx bx-movie'></i>
                <span>MoviePoll</span>
            </div>
            <nav>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Polls</a></li>
                    <li><a href="#">Genres</a></li>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Vote for Your Favorite Movie Genres</h1>
            <p>Join our community and help decide which movie genres deserve more attention from filmmakers.</p>
            <a href="#" class="btn">View Current Polls</a>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Our movie genre polling system makes it easy to voice your opinion and see what genres are most popular</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class='bx bx-check-square'></i>
                    </div>
                    <h3>Simple Voting</h3>
                    <p>Cast your vote with just one click. No complicated forms or registration required.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class='bx bx-bar-chart-alt'></i>
                    </div>
                    <h3>Real-time Results</h3>
                    <p>See how your favorite genres are performing with live updating results and statistics.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class='bx bx-group'></i>
                    </div>
                    <h3>Community Driven</h3>
                    <p>Join thousands of movie lovers helping shape the future of cinema through collective voting.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container footer-content">
            <p>&copy; 2023 MoviePoll. All rights reserved.</p>
            <div class="social-links">
                <a href="#"><i class='bx bxl-facebook'></i></a>
                <a href="#"><i class='bx bxl-twitter'></i></a>
                <a href="#"><i class='bx bxl-instagram'></i></a>
                <a href="#"><i class='bx bxl-youtube'></i></a>
            </div>
        </div>
    </footer>

    <!-- Chatbot Interface -->
    <div id="chat-icon">
        <i class='bx bx-message-rounded-dots'></i>
    </div>

    <div id="chat-container">
        <div id="chat-header">
            <h3>MoviePoll Assistant</h3>
            <div class="close-chat">
                <i class='bx bx-x'></i>
            </div>
        </div>
        <div id="chat-box"></div>
        <div id="user-input-container">
            <input type="text" id="user-input" placeholder="Type your message...">
            <button id="send-button">
                <i class='bx bx-send'></i>
            </button>
        </div>
    </div>

    <script>
        // DOM Elements
        const chatIcon = document.getElementById('chat-icon');
        const chatContainer = document.getElementById('chat-container');
        const closeChat = document.querySelector('.close-chat');
        const chatBox = document.getElementById('chat-box');
        const userInput = document.getElementById('user-input');
        const sendButton = document.getElementById('send-button');

        // Chatbot responses
        const botResponses = {
            "hi": "Hello! 👋 Welcome to MoviePoll. How can I help you today?",
            "hello": "Hi there! 😊 What would you like to know about our movie genre polling system?",
            "how are you": "I'm doing great, thanks for asking! Ready to help you with any questions about movie genres and polls.",
            "bye": "Goodbye! 🎬 Come back soon to vote for your favorite movie genres!",
            "what is this": "MoviePoll is a platform where you can vote for your favorite movie genres and see what genres are most popular among film fans.",
            "how to vote": "Voting is easy! Just go to the 'Polls' section, select your favorite genre, and click the vote button. Your vote will be counted immediately!",
            "see results": "You can view real-time results on each poll page. We show percentages and visual charts so you can see which genres are winning.",
            "create poll": "Currently, polls are created by our admin team. If you have suggestions for new genre polls, you can contact us through the website.",
            "popular genres": "Based on current voting, the most popular genres are: 1. Action/Adventure 2. Sci-Fi/Fantasy 3. Drama 4. Comedy 5. Thriller",
            "why vote": "Your vote helps filmmakers and studios understand what audiences really want to see! It's a way to influence future movie productions.",
            "change vote": "You can change your vote anytime before the poll closes. Just go back to the poll and select a different genre.",
            "poll schedule": "New genre polls are launched every month. Each poll runs for 30 days before we announce the results.",
            "contact": "You can reach our team through the 'Contact' page on our website or email us at support@moviepoll.com",
            "featured genres": "This month's featured genres include: Psychological Thriller, Cyberpunk Sci-Fi, and Historical Romance",
            "trending": "Currently trending in votes: Superhero movies are leading in Action, while Time Travel stories are popular in Sci-Fi",
            "default": "I'm not sure I understand. Could you ask about: how to vote, see results, popular genres, or poll schedule?"
        };

        // Common questions about the movie genre polling system
        const commonQuestions = [
            "How does the voting system work?",
            "Can I see past poll results?",
            "How often are new polls created?",
            "What's the most voted genre this month?",
            "Can I suggest new genres for polling?",
            "Is there a limit to how many times I can vote?",
            "How are the poll results used?",
            "What's the difference between genres and subgenres?",
            "Can I share my votes on social media?",
            "Are there any rewards for participating?"
        ];

        // Toggle chat visibility
        function toggleChat() {
            chatContainer.classList.toggle('active');
            if (chatContainer.classList.contains('active')) {
                chatIcon.style.display = 'none';
            } else {
                setTimeout(() => {
                    chatIcon.style.display = 'flex';
                }, 300);
            }
        }

        // Add message to chat
        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message', `${sender}-message`);
            messageDiv.textContent = text;
            chatBox.appendChild(messageDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Show typing indicator
        function showTyping() {
            const typingDiv = document.createElement('div');
            typingDiv.classList.add('typing-indicator');
            typingDiv.innerHTML = '<span></span><span></span><span></span>';
            chatBox.appendChild(typingDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
            return typingDiv;
        }

        // Get bot response
        function getBotResponse(userMessage) {
            const lowerMessage = userMessage.toLowerCase();
            let response = botResponses['default'];
            
            // Check for matching responses
            for (const [key, value] of Object.entries(botResponses)) {
                if (lowerMessage.includes(key)) {
                    response = value;
                    break;
                }
            }
            
            // Show typing indicator before response
            const typing = showTyping();
            
            // Simulate thinking time
            setTimeout(() => {
                chatBox.removeChild(typing);
                addMessage(response, 'bot');
                
                // Show common questions after greeting
                if (lowerMessage.includes('hi') || lowerMessage.includes('hello')) {
                    setTimeout(() => {
                        addMessage("Here are some common questions you might have:", 'bot');
                        commonQuestions.forEach((question, index) => {
                            setTimeout(() => {
                                addMessage(`${index + 1}. ${question}`, 'bot');
                            }, index * 300);
                        });
                    }, 500);
                }
            }, 1500);
        }

        // Send message
        function sendMessage() {
            const message = userInput.value.trim();
            if (message === '') return;
            
            addMessage(message, 'user');
            userInput.value = '';
            getBotResponse(message);
        }

        // Event listeners
        chatIcon.addEventListener('click', toggleChat);
        closeChat.addEventListener('click', toggleChat);
        sendButton.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });

        // Initial greeting
        setTimeout(() => {
            addMessage("Welcome to MoviePoll! I'm your assistant. How can I help you today?", 'bot');
        }, 1000);
    </script>
</body>
</html>