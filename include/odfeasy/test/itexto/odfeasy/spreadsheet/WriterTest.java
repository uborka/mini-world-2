/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy.spreadsheet;

import itexto.odfeasy.IODFDocument;
import java.io.File;
import java.io.OutputStream;
import org.junit.After;
import org.junit.AfterClass;
import org.junit.Before;
import org.junit.BeforeClass;
import org.junit.Test;
import static org.junit.Assert.*;

/**
 *
 * @author henriqueloboweissmann
 */
public class WriterTest {

    public WriterTest() {
    }

    @BeforeClass
    public static void setUpClass() throws Exception {
    }

    @AfterClass
    public static void tearDownClass() throws Exception {
    }

    @Before
    public void setUp() {
    }

    @After
    public void tearDown() {
    }

    private File file = new File("/Users/henriqueloboweissmann/Desktop/calc.ods");
    
    /**
     * Test of write method, of class Writer.
     */
    @Test
    public void testWrite_IODFDocument_File() throws Exception {
        System.out.println("write");
        Document doc = new Document();
        Spreadsheet sheet = new Spreadsheet(doc);
        sheet.setName("First sheet");
        
        Table table = new Table(sheet);
        table.setName("First plan");
        
        for (int i = 0; i < 4; i++) {
            Row row = new Row(table);
            row.setOrder(i + 2);
            for (int j = 0; j < 4; j++) {
                Cell cell = new Cell(row, j + 2);
                cell.setValue("Cell " + cell.getColumn() + ":" + cell.getRow().getOrder());
            }
        
        }
        
        Row row = new Row(table);
        row.setOrder(10);
        
        for (int i = 0; i < 10; i++) {
            Cell cell = new Cell(row, i);
            if (i > 0)
                cell.setColumn(i * 2);
            cell.setValue("Cell " + cell.getColumn() + ":" + cell.getRow().getOrder());
        }
        
        Writer writer = new Writer();
        writer.write(doc, file);
    }

    /**
     * Test of write method, of class Writer.
     */
    @Test
    public void testWrite_IODFDocument_OutputStream() throws Exception {
        System.out.println("write");
        IODFDocument document = null;
        OutputStream stream = null;
        Writer instance = new Writer();
        instance.write(document, stream);
        // TODO review the generated test code and remove the default call to fail.
        fail("The test case is a prototype.");
    }

}