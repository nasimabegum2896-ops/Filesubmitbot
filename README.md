# Telegram Bot for Render.com

## Setup Instructions

1. Push all files to your GitHub repository

2. Deploy on Render.com:
   - Connect your GitHub repository
   - Select "Docker" as environment
   - Set the name as "telegram-bot"
   - Deploy

3. After deployment, visit your app URL:
   `https://your-app-name.onrender.com`

4. Set the webhook by visiting:
   `https://api.telegram.org/bot8576540254:AAFPwxiXPk8HrARoXMCIQv-r-SunAj96JJU/setwebhook?url=https://your-app-name.onrender.com/index.php`

5. Test your bot by sending /start command

## File Structure