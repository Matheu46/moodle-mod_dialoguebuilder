# Moodle Activity: Dialogue Builder

The **Dialogue Builder** (`mod_dialoguebuilder`) is an activity plugin for Moodle that allows teachers to design interactive scenarios where students create and submit conversational scripts. It is ideal for creative writing, historical debates, language learning, and simulating real-world conversations.

## Features

- **Character Management:** Students can create multiple characters for their dialogue and assign custom avatars to each.
- **Interactive Script Editor:** A user-friendly, chat-like interface to construct dialogues line-by-line.
- **Emoji Support:** Integrated emoji picker to add expression and emotion to the dialogue.
- **Chat Playback:** A built-in "Player" that visually animates the dialogue on the screen, mimicking a real messaging app conversation.
- **Image Export:** Students and teachers can download the entire dialogue as an image to share or keep as a record (powered by `html2canvas`).
- **Gallery Mode:** Configurable settings allowing students to view their peers' submissions (e.g., completely open, only after submitting their own, or only after the activity is closed).
- **Grading & Feedback:** Integration with the Moodle Gradebook, allowing teachers to evaluate submissions and provide direct feedback.

## Usage

### For Teachers
1. **Create the Activity:** Turn editing on in your course, click "Add an activity or resource," and select "Dialogue Builder".
2. **Set Guidelines:** Provide a clear description and guidelines for the dialogue (e.g., "Create a conversation between Albert Einstein and Isaac Newton discussing gravity").
3. **Configure Settings:** Set up availability dates, grading criteria, and choose the Gallery access mode.
4. **Grade:** Once students submit, you can view their dialogues, watch the playback, and assign grades and feedback.

### For Students
1. **Add Characters:** Start your task by defining the characters participating in the dialogue. You can upload custom avatars for each.
2. **Write Lines:** Use the editor to add lines for each character. You can edit, delete, or rearrange them.
3. **Preview & Save:** Save drafts as you go, use the "Play" button to see how your conversation flows, and click "Submit" when you are finished!
4. **Download:** Export your final masterpiece as an image.

## Installation

### Method 1: Manual Installation via ZIP
1. Download the plugin as a ZIP file.
2. Log into your Moodle site as an Administrator.
3. Navigate to **Site administration > Plugins > Install plugins**.
4. Upload the ZIP file and select **Activity module (mod)** as the plugin type.
5. Follow the on-screen instructions to complete the installation.

### Method 2: Installation via Git
Clone this repository directly into your Moodle `mod` folder:

```bash
cd /path/to/moodle/mod
git clone https://github.com/yourusername/moodle-mod_dialoguebuilder.git dialoguebuilder
```
Then, log into Moodle as an administrator to trigger the installation/upgrade process.

## License

This plugin is licensed under the [GNU General Public License v3 or later](http://www.gnu.org/copyleft/gpl.html).

## Credits
- Developed by Matheus Mathias.
- Uses [html2canvas](https://html2canvas.hertzen.com/) (MIT License).
