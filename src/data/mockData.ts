import type { ChatItem, Message } from '../types';

export const mockUser = {
  username: 'Admin',
  password: '123',
};

export const onboardingSlides = [
  {
    title: 'Welcome to Chirp',
    body: 'Connecting you to the people who matter most, anywhere in the world.',
  },
  {
    title: 'Secure Messaging',
    body: 'End-to-end encryption keeps your private moments private.',
  },
  {
    title: 'Real-time and Fast',
    body: 'Lag-free communication powered by an optimized network.',
  },
];

export const mockChats: ChatItem[] = [
  {
    id: '1',
    name: 'Alex Rivera',
    preview: 'Hey, are we still meeting at 5?',
    time: '2m ago',
    unread: 2,
    online: true,
  },
  {
    id: '2',
    name: 'Jordan Doe',
    preview: 'You: Sent the file over!',
    time: '1h ago',
    unread: 0,
    online: false,
  },
  {
    id: '3',
    name: 'Sarah Chen',
    preview: 'Did you see the new design specs?',
    time: '3h ago',
    unread: 5,
    online: true,
  },
  {
    id: '4',
    name: 'Marketing Team',
    preview: 'Riley: The campaign is live!',
    time: 'Yesterday',
    unread: 0,
    online: false,
  },
];

export const mockMessages: Message[] = [
  {
    id: 'm1',
    from: 'them',
    kind: 'text',
    value: 'Hey, are we still meeting at 5?',
    time: '4:02 PM',
  },
  {
    id: 'm2',
    from: 'me',
    kind: 'text',
    value: "Yes, definitely. I'm finishing up some work.",
    time: '4:03 PM',
  },
  {
    id: 'm3',
    from: 'them',
    kind: 'image',
    value: '[Image] Cozy modern cafe',
    time: '4:03 PM',
  },
  {
    id: 'm4',
    from: 'me',
    kind: 'voice',
    value: '[Voice message] 0:12',
    time: '4:05 PM',
  },
];
