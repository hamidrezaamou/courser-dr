package com.example

import com.example.ui.components.toPersianDigits
import org.junit.Assert.*
import org.junit.Test

class ExampleUnitTest {
  @Test
  fun addition_isCorrect() {
    assertEquals(4, 2 + 2)
  }

  @Test
  fun testPersianDigitsConversion() {
    assertEquals("۰۱۲۳۴۵۶۷۸۹", toPersianDigits("0123456789"))
    assertEquals("۱۴۰۴/۰۶/۱۵", toPersianDigits("1404/06/15"))
    assertEquals("ساعت ۱۰:۳۰", toPersianDigits("ساعت 10:30"))
  }
}
