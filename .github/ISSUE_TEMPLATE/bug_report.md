name: Bug Report
description: Report a bug or issue in the application
title: "bug: [Short description of the bug]"
labels: ["bug"]
body:
  - type: markdown
    attributes:
      value: |
        Thank you for reporting a bug! Please fill out the form below to help us understand and fix it.
  - type: textarea
    id: description
    attributes:
      label: Description
      description: A clear and concise description of what the bug is.
    validations:
      required: true
  - type: textarea
    id: steps
    attributes:
      label: Steps to Reproduce
      description: Steps to reproduce the behavior.
      placeholder: |
        1. Go to '...'
        2. Click on '....'
        3. Scroll down to '....'
        4. See error
    validations:
      required: true
  - type: textarea
    id: expected
    attributes:
      label: Expected Behavior
      description: A clear and concise description of what you expected to happen.
    validations:
      required: true
  - type: textarea
    id: context
    attributes:
      label: Context & Environment
      description: |
        Provide any additional context or environment details (e.g. browser name/version, user role: owner/manager/cashier/designer/production, active branch).
    validations:
      required: false
